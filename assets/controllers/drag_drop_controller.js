// assets/controllers/drag_drop_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["card", "column", "tasksContainer", "columnHeader", "columnTitle"];

    connect() {
        console.log("DragDrop controller connected!");
        this.makeDraggable();
        this.makeDroppable();
        this.makeColumnsDraggable();
        this.makeColumnsDroppable();
    }

    // ========== TASK DRAG & DROP ==========
    makeDraggable() {
        this.cardTargets.forEach(card => {
            card.draggable = true;
            card.addEventListener('dragstart', this.dragStart.bind(this));
            card.addEventListener('dragend', this.dragEnd.bind(this));
        });
    }

    makeDroppable() {
        this.tasksContainerTargets.forEach(container => {
            container.addEventListener('dragover', this.dragOver.bind(this));
            container.addEventListener('drop', this.drop.bind(this));
            container.addEventListener('dragenter', this.dragEnter.bind(this));
            container.addEventListener('dragleave', this.dragLeave.bind(this));
        });
    }

    dragStart(event) {
        this.draggedCard = event.currentTarget;
        this.originalColumn = this.draggedCard.closest('[data-drag-drop-target="column"]');
        this.draggedCard.classList.add('dragging');
        event.dataTransfer.setData('text/plain', this.draggedCard.dataset.taskId);
        event.dataTransfer.effectAllowed = 'move';
    }

    dragEnd(event) {
        if (this.draggedCard) {
            this.draggedCard.classList.remove('dragging');
        }
        this.draggedCard = null;
        this.columnTargets.forEach(column => {
            column.classList.remove('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        });
    }

    dragEnter(event) {
        event.preventDefault();
        const targetContainer = event.currentTarget;
        const targetColumn = targetContainer.closest('[data-drag-drop-target="column"]');
        if (targetColumn) {
            targetColumn.classList.add('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        }
    }

    dragLeave(event) {
        const targetContainer = event.currentTarget;
        const targetColumn = targetContainer.closest('[data-drag-drop-target="column"]');
        if (targetColumn) {
            targetColumn.classList.remove('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        }
    }

    dragOver(event) {
        event.preventDefault();
    }

    async drop(event) {
        event.preventDefault();
        const targetTasksContainer = event.currentTarget;
        const targetColumn = targetTasksContainer.closest('[data-drag-drop-target="column"]');
        
        if (targetColumn) {
            targetColumn.classList.remove('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        }

        if (this.draggedCard) {
            const taskId = this.draggedCard.dataset.taskId;
            const newColId = targetColumn.dataset.colId;
            targetTasksContainer.appendChild(this.draggedCard);
            this.draggedCard.dataset.colId = newColId;
            await this.moveTask(taskId, newColId);
        }
    }

    // ========== COLUMN DRAG & DROP ==========
    makeColumnsDraggable() {
        this.columnTargets.forEach(column => {
            const header = column.querySelector('[data-drag-drop-target="columnHeader"]');
            if (header) {
                header.draggable = true;
                header.addEventListener('dragstart', this.columnDragStart.bind(this));
                header.addEventListener('dragend', this.columnDragEnd.bind(this));
            }
        });
    }

    makeColumnsDroppable() {
        this.columnTargets.forEach(column => {
            column.addEventListener('dragover', this.columnDragOver.bind(this));
            column.addEventListener('drop', this.columnDrop.bind(this));
            column.addEventListener('dragenter', this.columnDragEnter.bind(this));
            column.addEventListener('dragleave', this.columnDragLeave.bind(this));
        });
    }

    columnDragStart(event) {
        this.draggedColumn = event.currentTarget.closest('[data-drag-drop-target="column"]');
        this.originalColumnPosition = Array.from(this.draggedColumn.parentNode.children).indexOf(this.draggedColumn);
        this.draggedColumn.classList.add('column-dragging', 'opacity-50');
        event.dataTransfer.setData('text/plain', this.draggedColumn.dataset.colId);
        event.dataTransfer.effectAllowed = 'move';
    }

    columnDragEnd(event) {
        if (this.draggedColumn) {
            this.draggedColumn.classList.remove('column-dragging', 'opacity-50');
        }
        this.columnTargets.forEach(column => {
            column.classList.remove('column-drop-target', 'border-l-4', 'border-green-500');
        });
        this.draggedColumn = null;
    }

    columnDragEnter(event) {
        event.preventDefault();
        const targetColumn = event.currentTarget;
        if (targetColumn !== this.draggedColumn) {
            targetColumn.classList.add('column-drop-target', 'border-l-4', 'border-green-500');
        }
    }

    columnDragLeave(event) {
        const targetColumn = event.currentTarget;
        if (!targetColumn.contains(event.relatedTarget)) {
            targetColumn.classList.remove('column-drop-target', 'border-l-4', 'border-green-500');
        }
    }

    columnDragOver(event) {
        event.preventDefault();
    }

    async columnDrop(event) {
        event.preventDefault();
        const targetColumn = event.currentTarget;
        targetColumn.classList.remove('column-drop-target', 'border-l-4', 'border-green-500');

        if (this.draggedColumn && targetColumn !== this.draggedColumn) {
            const newPosition = Array.from(targetColumn.parentNode.children).indexOf(targetColumn);
            const draggedColId = this.draggedColumn.dataset.colId;

            // Visual move
            if (newPosition < this.originalColumnPosition) {
                targetColumn.parentNode.insertBefore(this.draggedColumn, targetColumn);
            } else {
                targetColumn.parentNode.insertBefore(this.draggedColumn, targetColumn.nextSibling);
            }

            // Send to server
            await this.moveColumn(draggedColId, newPosition);
        }
    }

    // ========== COLUMN ACTIONS - SZYBKIE USUWANIE ==========
    async deleteColumn(event) {
        event.preventDefault();
        const columnElement = event.currentTarget.closest('[data-drag-drop-target="column"]');
        const colId = columnElement.dataset.colId;
        const colName = columnElement.querySelector('[data-drag-drop-target="columnTitle"]')?.textContent || 'tę kolumnę';
        const taskCount = columnElement.querySelectorAll('[data-drag-drop-target="card"]').length;

        let confirmMessage = `Czy na pewno chcesz usunąć kolumnę "${colName}"?`;
        if (taskCount > 0) {
            confirmMessage += `\n\nUWAGA: Kolumna zawiera ${taskCount} zadań, które również zostaną usunięte!`;
        }

        if (!confirm(confirmMessage)) {
            return;
        }

        // ⚡ SZYBKIE USUWANIE - usuń z DOM natychmiast
        const parent = columnElement.parentNode;
        const nextSibling = columnElement.nextSibling;
        const columnClone = columnElement.cloneNode(true);
        
        // Usuń kolumnę z DOM od razu (bez czekania na serwer)
        columnElement.remove();
        this.showNotification('Usuwanie kolumny...', 'info');

        try {
            const response = await fetch(`/column/${colId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showNotification('Kolumna usunięta pomyślnie!', 'success');
                
                // USUNIĘTO logikę sprawdzającą czy to była ostatnia kolumna
                // Zawsze samo zachowanie - pokazuj sukces i opcjonalnie przekieruj
                if (data.redirect) {
                    setTimeout(() => window.location.href = data.redirect, 1000);
                }
            } else {
                // W przypadku błędu serwera - przywróć kolumnę
                this.restoreColumn(parent, columnClone, nextSibling);
                this.showNotification('Błąd: ' + (data.error || 'Nieznany błąd.'), 'error');
            }
        } catch (error) {
            console.error('Error deleting column:', error);
            // W przypadku błędu sieci - przywróć kolumnę
            this.restoreColumn(parent, columnClone, nextSibling);
            this.showNotification('Wystąpił błąd podczas usuwania kolumny.', 'error');
        }
    }

    
    // Metoda pomocnicza do przywracania kolumny w przypadku błędu
    restoreColumn(parent, columnClone, nextSibling) {
        if (nextSibling) {
            parent.insertBefore(columnClone, nextSibling);
        } else {
            parent.appendChild(columnClone);
        }
        
        // Ponownie podepnij event listenery do przywróconej kolumny
        this.makeColumnsDraggable();
        this.makeColumnsDroppable();
        this.makeDraggable();
        this.makeDroppable();
    }

    editColumnName(event) {
        const columnTitle = event.currentTarget;
        const currentName = columnTitle.textContent.trim();
        const colId = columnTitle.closest('[data-drag-drop-target="column"]').dataset.colId;

        // Stwórz pole input do edycji
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentName;
        input.className = 'font-bold text-lg bg-transparent border-b-2 border-blue-500 focus:outline-none focus:border-blue-700 w-full';
        
        // Zamień tytuł na input
        columnTitle.style.display = 'none';
        columnTitle.parentNode.insertBefore(input, columnTitle);
        input.focus();
        input.select();

        const saveEdit = async () => {
            const newName = input.value.trim();
            if (newName && newName !== currentName) {
                await this.updateColumnName(colId, newName);
                columnTitle.textContent = newName;
            }
            input.remove();
            columnTitle.style.display = 'block';
        };

        const cancelEdit = () => {
            input.remove();
            columnTitle.style.display = 'block';
        };

        input.addEventListener('blur', saveEdit);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveEdit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEdit();
            }
        });
    }

    async updateColumnName(colId, newName) {
        try {
            const formData = new FormData();
            formData.append('name', newName);
            formData.append('_method', 'PUT');

            const response = await fetch(`/column/${colId}/edit`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Nazwa kolumny zaktualizowana!', 'success');
            } else {
                throw new Error(data.errors ? data.errors.join(', ') : 'Nieznany błąd');
            }
        } catch (error) {
            console.error('Error updating column name:', error);
            this.showNotification(`Błąd: ${error.message}`, 'error');
        }
    }

    // ========== API CALLS ==========
    async moveTask(taskId, colId) {
        try {
            const response = await fetch(`/task/${taskId}/move`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ colId: colId })
            });

            if (response.ok) {
                console.log('Task moved successfully');
                this.updateTaskCounts();
            } else {
                const errorData = await response.json();
                console.error('Error moving task:', errorData.error);
                this.revertTaskMove();
                this.showNotification(`Błąd: ${errorData.error || 'Nieznany błąd.'}`, 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            this.revertTaskMove();
            this.showNotification(`Błąd sieci: ${error.message}`, 'error');
        }
    }

    async moveColumn(colId, newPosition) {
        try {
            const response = await fetch(`/column/${colId}/move`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ position: newPosition })
            });

            if (response.ok) {
                console.log('Column moved successfully');
                this.showNotification('Kolumna przeniesiona!', 'success');
            } else {
                const errorData = await response.json();
                console.error('Error moving column:', errorData.error);
                this.revertColumnMove();
                this.showNotification(`Błąd: ${errorData.error || 'Nieznany błąd.'}`, 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            this.revertColumnMove();
            this.showNotification(`Błąd sieci: ${error.message}`, 'error');
        }
    }

    // ========== UTILITY METHODS ==========
    updateTaskCounts() {
        this.columnTargets.forEach(columnElement => {
            const tasksContainer = columnElement.querySelector('[data-drag-drop-target="tasksContainer"]');
            const taskCount = tasksContainer.querySelectorAll('[data-drag-drop-target="card"]').length;
            const countSpan = columnElement.querySelector('.task-count');
            if (countSpan) {
                countSpan.textContent = taskCount;
            }
        });
    }

    revertTaskMove() {
        if (this.draggedCard && this.originalColumn) {
            const originalContainer = this.originalColumn.querySelector('[data-drag-drop-target="tasksContainer"]');
            originalContainer.appendChild(this.draggedCard);
            this.draggedCard.dataset.colId = this.originalColumn.dataset.colId;
            this.updateTaskCounts();
        }
    }

    revertColumnMove() {
        if (this.draggedColumn && this.originalColumnPosition !== undefined) {
            const parent = this.draggedColumn.parentNode;
            const children = Array.from(parent.children);
            if (this.originalColumnPosition < children.length) {
                parent.insertBefore(this.draggedColumn, children[this.originalColumnPosition]);
            } else {
                parent.appendChild(this.draggedColumn);
            }
        }
    }

    // ========== POWIADOMIENIA ==========
    showNotification(message, type = 'success') {
        // Usuń istniejące powiadomienie
        const existing = document.querySelector('.notification-toast');
        if (existing) {
            existing.remove();
        }

        // Ustaw kolor na podstawie typu
        let bgColor, iconSvg;
        switch(type) {
            case 'success':
                bgColor = 'bg-green-500';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                break;
            case 'error':
                bgColor = 'bg-red-500';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                break;
            case 'info':
                bgColor = 'bg-blue-500';
                iconSvg = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                break;
            default:
                bgColor = 'bg-gray-500';
                iconSvg = '';
        }

        const notification = document.createElement('div');
        notification.className = `notification-toast fixed top-4 right-4 ${bgColor} text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center transform translate-x-full transition-transform duration-300 ease-out`;
        notification.innerHTML = `${iconSvg}<span>${message}</span>`;
        
        document.body.appendChild(notification);

        // Animacja pojawiania się
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Automatyczne ukrycie po 3 sekundach
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
    }
}