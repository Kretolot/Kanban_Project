// assets/controllers/drag_drop_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["card", "column", "tasksContainer"]; // Dodano tasksContainer

    connect() {
        console.log("DragDrop controller connected!");
        this.makeDraggable();
        this.makeDroppable();

        // Upewnij się, że ten listener jest tylko raz na kontenerze
        // (W przeciwieństwie do poprzedniego przykładu, teraz używamy targetów Stimulus)
    }

    makeDraggable() {
        this.cardTargets.forEach(card => {
            card.draggable = true;
            card.addEventListener('dragstart', this.dragStart.bind(this));
            card.addEventListener('dragend', this.dragEnd.bind(this));
        });
    }

    makeDroppable() {
        this.tasksContainerTargets.forEach(container => { // Zmieniono na tasksContainerTargets
            container.addEventListener('dragover', this.dragOver.bind(this));
            container.addEventListener('drop', this.drop.bind(this));
            container.addEventListener('dragenter', this.dragEnter.bind(this));
            container.addEventListener('dragleave', this.dragLeave.bind(this));
        });
    }

    dragStart(event) {
        this.draggedCard = event.currentTarget; // Zapisz referencję do przeciąganej karty
        this.originalColumn = this.draggedCard.closest('[data-drag-drop-target="column"]'); // Zapisz oryginalną kolumnę
        
        // Dodaj klasę do przeciąganego elementu (Tailwind opacity-50)
        // Możesz użyć 'dragging' z CSS, jak w poprzednim przykładzie
        this.draggedCard.classList.add('dragging'); 
        
        event.dataTransfer.setData('text/plain', this.draggedCard.dataset.taskId);
        event.dataTransfer.effectAllowed = 'move'; // Opcjonalnie, dla lepszego wizualnego feedbacku
    }

    dragEnd(event) {
        if (this.draggedCard) {
            this.draggedCard.classList.remove('dragging');
        }
        this.draggedCard = null; // Zresetuj przeciąganą kartę

        // Usuń klasy 'drag-over' ze wszystkich kolumn
        this.columnTargets.forEach(column => {
            column.classList.remove('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        });
    }

    dragEnter(event) {
        event.preventDefault();
        // Sprawdzamy, czy event.target (czyli element, nad którym jesteśmy) jest wewnątrz kontenera zadań
        const targetContainer = event.currentTarget; 
        const targetColumn = targetContainer.closest('[data-drag-drop-target="column"]');

        if (targetColumn) {
            targetColumn.classList.add('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        }
    }

    dragLeave(event) {
        // Sprawdzamy, czy event.target (czyli element, który opuszczamy) jest wewnątrz kontenera zadań
        const targetContainer = event.currentTarget;
        const targetColumn = targetContainer.closest('[data-drag-drop-target="column"]');

        if (targetColumn) {
            targetColumn.classList.remove('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        }
    }

    dragOver(event) {
        event.preventDefault();
        // W tym miejscu możesz zaimplementować logikę "insert-target"
        // aby wizualnie pokazać, gdzie karta zostanie upuszczona.
        // Na razie wystarczy samo dragOver, aby event drop zadziałał.
    }

    async drop(event) {
        event.preventDefault();

        // Kontener zadań, na który upuszczono kartę
        const targetTasksContainer = event.currentTarget;
        // Kolumna, do której należy ten kontener zadań
        const targetColumn = targetTasksContainer.closest('[data-drag-drop-target="column"]');
        
        // Usuń klasy wizualne po upuszczeniu
        if (targetColumn) {
            targetColumn.classList.remove('bg-blue-50', 'border-2', 'border-blue-300', 'border-dashed');
        }

        if (this.draggedCard) {
            const taskId = this.draggedCard.dataset.taskId;
            const newColId = targetColumn.dataset.colId; // Pobierz ID nowej kolumny

            // *** Ważna zmiana: Wizualne przeniesienie elementu w DOM ***
            targetTasksContainer.appendChild(this.draggedCard);

            // Zaktualizuj data-col-id na przeniesionej karcie
            this.draggedCard.dataset.colId = newColId;

            // Wyślij żądanie do serwera
            await this.moveTask(taskId, newColId); // Czekaj na zakończenie operacji na serwerze
        }
    }

    async moveTask(taskId, colId) {
        try {
            const response = await fetch(`/task/${taskId}/move`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest', // Dobrze, że to masz!
                },
                body: JSON.stringify({ colId: colId })
            });

            if (response.ok) {
                console.log('Zadanie przeniesione pomyślnie na serwerze.');
                // NIE używamy location.reload() tutaj
                // Zamiast tego aktualizujemy liczniki zadań
                this.updateTaskCounts();

                // Opcjonalnie: możesz dodać flash message po sukcesie
                // np. poprzez wywołanie dispatch lub bezpośrednie manipulowanie DOM
            } else {
                const errorData = await response.json();
                console.error('Błąd podczas przenoszenia zadania:', errorData.error || response.statusText);
                // Przywróć kartę do poprzedniego miejsca, jeśli serwer zwrócił błąd
                if (this.draggedCard && this.originalColumn) {
                    this.originalColumn.querySelector('[data-drag-drop-target="tasksContainer"]').appendChild(this.draggedCard);
                    this.draggedCard.dataset.colId = this.originalColumn.dataset.colId;
                    this.updateTaskCounts(); // Zaktualizuj liczniki po przywróceniu
                }
                alert(`Błąd: ${errorData.error || 'Wystąpił nieznany błąd.'}`);
            }
        } catch (error) {
            console.error('Błąd sieci lub serwera:', error);
            // Przywróć kartę do poprzedniego miejsca w przypadku błędu sieci
            if (this.draggedCard && this.originalColumn) {
                this.originalColumn.querySelector('[data-drag-drop-target="tasksContainer"]').appendChild(this.draggedCard);
                this.draggedCard.dataset.colId = this.originalColumn.dataset.colId;
                this.updateTaskCounts(); // Zaktualizuj liczniki po przywróceniu
            }
            alert(`Wystąpił błąd podczas przenoszenia zadania: ${error.message}`);
        }
    }

    updateTaskCounts() {
        this.columnTargets.forEach(columnElement => {
            const tasksContainer = columnElement.querySelector('[data-drag-drop-target="tasksContainer"]');
            const taskCount = tasksContainer.querySelectorAll('[data-drag-drop-target="card"]').length;
            // Znajdź span z liczbą zadań po klasie 'task-count' lub innej unikalnej
            const countSpan = columnElement.querySelector('.task-count'); 
            if (countSpan) {
                countSpan.textContent = taskCount;
            }
        });
    }
}