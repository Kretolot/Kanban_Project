import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        // Modal dodawania zadania
        "modal", // Główny kontener modala dodawania zadania
        "form", // Formularz dodawania zadania
        "colIdInput", // Ukryte pole input do ID kolumny
        "taskErrors", // Kontener na błędy dodawania zadania

        // Modal edycji zadania
        "editModal", // Główny kontener modala edycji zadania
        "editForm", // Formularz edycji zadania
        "editTaskId", // Ukryte pole input do ID zadania
        "editTitle", // Pole tytułu zadania
        "editDescription", // Pole opisu zadania
        "editTaskErrors", // Kontener na błędy edycji zadania

        // Modal dodawania tablicy
        "addBoardForm", // Formularz dodawania tablicy
        "addBoardErrors", // Kontener na błędy dodawania tablicy

        // Modal dodawania kolumny
        "addColumnModal", // Główny kontener modala dodawania kolumny
        "addColumnForm", // Formularz dodawania kolumny
        "addColumnErrors" // Kontener na błędy dodawania kolumny
    ];

    connect() {
        console.log('Modal controller connected');
        // Ukryj modale na start, ale TYLKO jeśli dany modal istnieje w DOM
        if (this.hasModalTarget) { // Dotyczy modala dodawania zadania LUB dodawania tablicy
            this.modalTarget.classList.add('hidden');
            this.modalTarget.classList.remove('flex');
            if (this.hasFormTarget) {
                this.formTarget.reset();
            }
            if (this.hasTaskErrorsTarget) {
                this.clearErrors(this.taskErrorsTarget);
            }
            if (this.hasAddBoardFormTarget) {
                this.addBoardFormTarget.reset();
            }
            if (this.hasAddBoardErrorsTarget) {
                this.clearErrors(this.addBoardErrorsTarget);
            }
        }
        if (this.hasEditModalTarget) { // Dotyczy modala edycji zadania
            this.editModalTarget.classList.add('hidden');
            this.editModalTarget.classList.remove('flex');
            if (this.hasEditFormTarget) {
                this.editFormTarget.reset();
            }
            if (this.hasEditTaskErrorsTarget) {
                this.clearErrors(this.editTaskErrorsTarget);
            }
        }
        if (this.hasAddColumnModalTarget) { // Dotyczy modala dodawania kolumny
            this.addColumnModalTarget.classList.add('hidden');
            this.addColumnModalTarget.classList.remove('flex');
            if (this.hasAddColumnFormTarget) {
                this.addColumnFormTarget.reset();
            }
            if (this.hasAddColumnErrorsTarget) {
                this.clearErrors(this.addColumnErrorsTarget);
            }
        }
    }

    // ========== METODY DO MODALA DODAWANIA ZADANIA ==========
    open(event) {
        if (this.hasColIdInputTarget && event.currentTarget.dataset.colId) {
            this.colIdInputTarget.value = event.currentTarget.dataset.colId;
        }
        if (this.hasTaskErrorsTarget) {
            this.clearErrors(this.taskErrorsTarget);
        }
        if (this.hasModalTarget) {
            this.modalTarget.classList.remove('hidden');
            this.modalTarget.classList.add('flex');
        }
    }

    close() {
        if (this.hasModalTarget) {
            this.modalTarget.classList.add('hidden');
            this.modalTarget.classList.remove('flex');
        }
        if (this.hasFormTarget) {
            this.formTarget.reset();
        }
        if (this.hasTaskErrorsTarget) {
            this.clearErrors(this.taskErrorsTarget);
        }
    }

    // ========== METODY DO MODALA EDYCJI ZADANIA ==========
    openEditModal(event) {
        if (!this.hasEditModalTarget) {
            console.error("Edit modal target not found.");
            return;
        }

        const card = event.currentTarget;
        const taskId = card.dataset.taskId;
        const title = card.querySelector('h4')?.innerText || '';
        const description = card.querySelector('p')?.innerText || '';

        if (this.hasEditTaskIdTarget) this.editTaskIdTarget.value = taskId;
        if (this.hasEditTitleTarget) this.editTitleTarget.value = title;
        if (this.hasEditDescriptionTarget) this.editDescriptionTarget.value = description;

        if (this.hasEditFormTarget) {
            this.editFormTarget.action = `/task/${taskId}/edit`;
        }
        if (this.hasEditTaskErrorsTarget) {
            this.clearErrors(this.editTaskErrorsTarget);
        }
        this.editModalTarget.classList.remove('hidden');
        this.editModalTarget.classList.add('flex');
    }

    closeEditModal() {
        if (this.hasEditModalTarget) {
            this.editModalTarget.classList.add('hidden');
            this.editModalTarget.classList.remove('flex');
        }
        if (this.hasEditFormTarget) {
            this.editFormTarget.reset();
        }
        if (this.hasEditTaskErrorsTarget) {
            this.clearErrors(this.editTaskErrorsTarget);
        }
    }

    // ========== METODY DO MODALA DODAWANIA TABLICY ==========
    openAddBoardModal() {
        console.log('Metoda openAddBoardModal została wywołana!');
        if (this.hasModalTarget) {
            this.modalTarget.classList.remove('hidden');
            this.modalTarget.classList.add('flex');
        }
        if (this.hasAddBoardErrorsTarget) {
            this.clearErrors(this.addBoardErrorsTarget);
        }
        if (this.hasAddBoardFormTarget) {
            this.addBoardFormTarget.reset();
        }
    }

    closeAddBoardModal() {
        if (this.hasModalTarget) {
            this.modalTarget.classList.add('hidden');
            this.modalTarget.classList.remove('flex');
        }
        if (this.hasAddBoardFormTarget) {
            this.addBoardFormTarget.reset();
        }
        if (this.hasAddBoardErrorsTarget) {
            this.clearErrors(this.addBoardErrorsTarget);
        }
    }

    // ========== METODY DO MODALA DODAWANIA KOLUMNY ==========
    openAddColumnModal() {
        console.log('Metoda openAddColumnModal została wywołana!');
        if (this.hasAddColumnModalTarget) {
            this.addColumnModalTarget.classList.remove('hidden');
            this.addColumnModalTarget.classList.add('flex');
        }
        if (this.hasAddColumnErrorsTarget) {
            this.clearErrors(this.addColumnErrorsTarget);
        }
        if (this.hasAddColumnFormTarget) {
            this.addColumnFormTarget.reset();
        }
    }

    closeAddColumnModal() {
        if (this.hasAddColumnModalTarget) {
            this.addColumnModalTarget.classList.add('hidden');
            this.addColumnModalTarget.classList.remove('flex');
        }
        if (this.hasAddColumnFormTarget) {
            this.addColumnFormTarget.reset();
        }
        if (this.hasAddColumnErrorsTarget) {
            this.clearErrors(this.addColumnErrorsTarget);
        }
    }

    // ========== METODY POMOCNICZE ==========
    displayErrors(errorContainer, errors) {
        errorContainer.innerHTML = '';
        if (errors && errors.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'list-disc list-inside text-red-600';
            errors.forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                ul.appendChild(li);
            });
            errorContainer.appendChild(ul);
        }
    }

    clearErrors(errorContainer) {
        errorContainer.innerHTML = '';
    }

    // ========== WYSYŁANIE FORMULARZY ==========
    async submitForm(event) {
        event.preventDefault();

        if (!this.hasFormTarget) {
            console.error("Missing form target for task form.");
            return;
        }
        const form = this.formTarget;
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
                this.close();
            } else {
                if (this.hasTaskErrorsTarget) {
                    this.displayErrors(this.taskErrorsTarget, data.errors || ['Wystąpił nieznany błąd.']);
                }
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            if (this.hasTaskErrorsTarget) {
                this.displayErrors(this.taskErrorsTarget, ['Wystąpił błąd sieci lub serwera.']);
            }
        }
    }

    async submitEditForm(event) {
        event.preventDefault();

        if (!this.hasEditFormTarget || !this.hasEditTaskIdTarget) {
            console.error("Missing targets for edit task form.");
            return;
        }
        const form = this.editFormTarget;
        const formData = new FormData(form);

        try {
            const response = await fetch(`/task/${this.editTaskIdTarget.value}/edit`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
                this.closeEditModal();
            } else {
                if (this.hasEditTaskErrorsTarget) {
                    this.displayErrors(this.editTaskErrorsTarget, data.errors || ['Wystąpił nieznany błąd.']);
                }
            }
        } catch (error) {
            console.error('Error submitting edit form:', error);
            if (this.hasEditTaskErrorsTarget) {
                this.displayErrors(this.editTaskErrorsTarget, ['Wystąpił błąd sieci lub serwera.']);
            }
        }
    }

    async submitAddBoardForm(event) {
        event.preventDefault();

        if (!this.hasAddBoardFormTarget) {
            console.error("Missing addBoardForm target.");
            return;
        }
        const form = this.addBoardFormTarget;
        const formData = new FormData(form);
        const boardName = formData.get('name');

        if (this.hasAddBoardErrorsTarget) {
            this.clearErrors(this.addBoardErrorsTarget);
        }

        try {
            const response = await fetch(form.action, {
                method: form.method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: boardName }),
            });

            const data = await response.json();

            if (!response.ok) {
                if (this.hasAddBoardErrorsTarget) {
                    this.displayErrors(this.addBoardErrorsTarget, data.errors || [data.message || 'Wystąpił nieznany błąd.']);
                } else {
                    console.error("Błąd dodawania tablicy:", data);
                }
                return;
            }

            console.log('Tablica dodana pomyślnie:', data);
            this.closeAddBoardModal();
            window.location.reload();

        } catch (error) {
            console.error('Error submitting add board form:', error);
            if (this.hasAddBoardErrorsTarget) {
                this.displayErrors(this.addBoardErrorsTarget, ['Wystąpił błąd sieci lub serwera.']);
            }
        }
    }

    async submitAddColumnForm(event) {
        event.preventDefault();

        if (!this.hasAddColumnFormTarget) {
            console.error("Missing addColumnForm target.");
            return;
        }

        const form = this.addColumnFormTarget;
        const formData = new FormData(form);
        const columnName = formData.get('name');

        // Pobierz boardId z kontrolera drag-drop
        const dragDropController = this.application.getControllerForElementAndIdentifier(
            document.querySelector('[data-controller*="drag-drop"]'), 
            'drag-drop'
        );
        const boardId = dragDropController?.element?.dataset?.boardId;

        if (!boardId) {
            console.error("Nie można znaleźć boardId");
            if (this.hasAddColumnErrorsTarget) {
                this.displayErrors(this.addColumnErrorsTarget, ['Nie można znaleźć ID tablicy.']);
            }
            return;
        }

        if (this.hasAddColumnErrorsTarget) {
            this.clearErrors(this.addColumnErrorsTarget);
        }

        try {
            const response = await fetch(`/board/${boardId}/column/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: columnName }),
            });

            const data = await response.json();

            if (!response.ok) {
                if (this.hasAddColumnErrorsTarget) {
                    this.displayErrors(this.addColumnErrorsTarget, data.errors || [data.message || 'Wystąpił nieznany błąd.']);
                } else {
                    console.error("Błąd dodawania kolumny:", data);
                }
                return;
            }

            console.log('Kolumna dodana pomyślnie:', data);
            this.closeAddColumnModal();
            
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.reload();
            }

        } catch (error) {
            console.error('Error submitting add column form:', error);
            if (this.hasAddColumnErrorsTarget) {
                this.displayErrors(this.addColumnErrorsTarget, ['Wystąpił błąd sieci lub serwera.']);
            }
        }
    }

    // ========== USUWANIE ==========
    async deleteTask(event) {
        event.preventDefault();
        if (!confirm('Czy na pewno chcesz usunąć to zadanie?')) {
            return;
        }

        if (!this.hasEditTaskIdTarget) {
            console.error("Missing editTaskId target for deleting task.");
            return;
        }
        const taskId = this.editTaskIdTarget.value;

        try {
            const response = await fetch(`/task/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
                this.closeEditModal();
            } else {
                alert('Błąd podczas usuwania zadania: ' + (data.error || 'Nieznany błąd.'));
            }
        } catch (error) {
            console.error('Error deleting task:', error);
            alert('Wystąpił błąd sieci lub serwera podczas usuwania zadania.');
        }
    }

    async deleteBoard(event) {
        event.preventDefault();
        const boardId = event.currentTarget.dataset.boardId;
        const boardName = event.currentTarget.dataset.boardName;

        if (!confirm(`Czy na pewno chcesz usunąć tablicę "${boardName}"? Wszystkie zadania w liście "Ukończone" zostaną trwale usunięte.`)) {
            return;
        }

        try {
            const response = await fetch(`/board/${boardId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                alert('Błąd podczas usuwania tablicy: ' + (data.error || 'Nieznany błąd.'));
            }
        } catch (error) {
            console.error('Error deleting board:', error);
            alert('Wystąpił błąd sieci lub serwera podczas usuwania tablicy.');
        }
    }
}