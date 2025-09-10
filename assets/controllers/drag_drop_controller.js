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

    // ========== EXISTING TASK DRAG & DROP ==========
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

    // ========== COLUMN ACTIONS ==========
    async deleteColumn(event) {
        event.preventDefault();
        const columnElement = event.currentTarget.closest('[data-drag-drop-target="column"]');
        const colId = columnElement.dataset.colId;
        const colName = columnElement.querySelector('[data-drag-drop-target="columnTitle"]')?.textContent || 'this column';
        const taskCount = columnElement.querySelectorAll('[data-drag-drop-target="card"]').length;

        let confirmMessage = `Czy na pewno chcesz usunąć kolumnę "${colName}"?`;
        if (taskCount > 0) {
            confirmMessage += `\n\nUWAGA: Kolumna zawiera ${taskCount} zadań, które również zostaną usunięte!`;
        }

        if (!confirm(confirmMessage)) {
            return;
        }

        try {
            const response = await fetch(`/column/${colId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                columnElement.remove();
                this.showSuccess('Kolumna usunięta pomyślnie!');
                if (data.redirect) {
                    setTimeout(() => window.location.href = data.redirect, 1000);
                }
            } else {
                alert('Błąd: ' + (data.error || 'Nieznany błąd.'));
            }
        } catch (error) {
            console.error('Error deleting column:', error);
            alert('Wystąpił błąd podczas usuwania kolumny.');
        }
    }

    editColumnName(event) {
        const columnTitle = event.currentTarget;
        const currentName = columnTitle.textContent.trim();
        const colId = columnTitle.closest('[data-drag-drop-target="column"]').dataset.colId;

        // Create input for editing
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentName;
        input.className = 'font-bold text-lg bg-transparent border-b-2 border-blue-500 focus:outline-none focus:border-blue-700 w-full';
        
        // Replace title with input
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
                this.showSuccess('Nazwa kolumny zaktualizowana!');
            } else {
                throw new Error(data.errors ? data.errors.join(', ') : 'Nieznany błąd');
            }
        } catch (error) {
            console.error('Error updating column name:', error);
            alert(`Błąd: ${error.message}`);
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
                alert(`Błąd: ${errorData.error || 'Nieznany błąd.'}`);
            }
        } catch (error) {
            console.error('Network error:', error);
            this.revertTaskMove();
            alert(`Błąd sieci: ${error.message}`);
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
                this.showSuccess('Kolumna przeniesiona!');
            } else {
                const errorData = await response.json();
                console.error('Error moving column:', errorData.error);
                this.revertColumnMove();
                alert(`Błąd: ${errorData.error || 'Nieznany błąd.'}`);
            }
        } catch (error) {
            console.error('Network error:', error);
            this.revertColumnMove();
            alert(`Błąd sieci: ${error.message}`);
        }
    }

    // USUNIĘTO METODĘ addColumn - teraz obsługiwana przez modal_controller

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

    showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
        successDiv.textContent = message;
        document.body.appendChild(successDiv);

        setTimeout(() => {
            successDiv.remove();
        }, 3000);
    }
}