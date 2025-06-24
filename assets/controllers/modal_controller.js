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

        // Modal dodawania tablicy - Upewnij się, że 'modal' odnosi się do GŁÓWNEGO kontenera modala
        // W twoim HTML modal dodawania tablicy ma data-modal-target="modal", więc nie potrzebujesz osobnego targetu dla niego.
        // ALE: formularz w środku MUSI mieć unikalny target.
        // Tutaj 'addBoardForm' jest poprawne, ponieważ zmieniliśmy to w HTML.
        "addBoardForm", // Formularz dodawania tablicy
        "addBoardErrors" // Kontener na błędy dodawania tablicy
    ];

    connect() {
        console.log('Modal controller connected');
        // Ukryj modale na start, ale TYLKO jeśli dany modal istnieje w DOM
        if (this.hasModalTarget) { // Dotyczy modala dodawania zadania LUB dodawania tablicy (jeśli ma data-modal-target="modal")
            this.modalTarget.classList.add('hidden');
            this.modalTarget.classList.remove('flex');
            // Resetowanie formularza dodawania zadania i jego błędów
            if (this.hasFormTarget) {
                this.formTarget.reset();
            }
            if (this.hasTaskErrorsTarget) {
                this.clearErrors(this.taskErrorsTarget);
            }
            // Resetowanie formularza dodawania tablicy i jego błędów (bo używa tego samego głównego kontenera modala)
            if (this.hasAddBoardFormTarget) { // Teraz to będzie działać poprawnie
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
    }

    // Metody do modala dodawania zadania (bez zmian, działają poprawnie z 'form' targetem)
    open(event) {
        if (this.hasColIdInputTarget && event.currentTarget.dataset.colId) {
            this.colIdInputTarget.value = event.currentTarget.dataset.colId;
        }
        if (this.hasTaskErrorsTarget) {
            this.clearErrors(this.taskErrorsTarget);
        }
        if (this.hasModalTarget) { // Odnosi się do głównego modala dla zadań
            this.modalTarget.classList.remove('hidden');
            this.modalTarget.classList.add('flex');
        }
    }

    close() {
        if (this.hasModalTarget) { // Odnosi się do głównego modala dla zadań
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

    // Metody do modala edycji zadania (bez zmian)
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

    // Metody do modala dodawania tablicy
    openAddBoardModal() {
        console.log('Metoda openAddBoardModal została wywołana!');
        if (this.hasModalTarget) { // Używamy this.modalTarget, ponieważ główny div modala tablicy ma data-modal-target="modal"
            this.modalTarget.classList.remove('hidden');
            this.modalTarget.classList.add('flex');
        }
        if (this.hasAddBoardErrorsTarget) {
            this.clearErrors(this.addBoardErrorsTarget);
        }
        if (this.hasAddBoardFormTarget) { // To jest teraz poprawne odwołanie
            this.addBoardFormTarget.reset();
        }
    }

    closeAddBoardModal() {
        if (this.hasModalTarget) { // Używamy this.modalTarget, ponieważ główny div modala tablicy ma data-modal-target="modal"
            this.modalTarget.classList.add('hidden');
            this.modalTarget.classList.remove('flex');
        }
        if (this.hasAddBoardFormTarget) { // To jest teraz poprawne odwołanie
            this.addBoardFormTarget.reset();
        }
        if (this.hasAddBoardErrorsTarget) {
            this.clearErrors(this.addBoardErrorsTarget);
        }
    }

    // Metoda do wyświetlania błędów w kontenerze
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

    // Metoda do czyszczenia błędów w kontenerze
    clearErrors(errorContainer) {
        errorContainer.innerHTML = '';
    }

    // Pozostawiamy submitForm bez zmian, ponieważ dotyczy formularza zadań
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
            const response = await fetch(`/task/${this.editTaskIdTarget.value}/edit`, { // Użyj wartości z targetu bezpośrednio
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

    async submitAddBoardForm(event) {
        event.preventDefault();

        if (!this.hasAddBoardFormTarget) {
            console.error("Missing addBoardForm target."); // To już nie powinno się pokazać po zmianie HTML
            return;
        }
        const form = this.addBoardFormTarget;
        const formData = new FormData(form);
        const boardName = formData.get('name'); // Pobierz wartość z pola input o nazwie 'name'

        // Oczyść poprzednie błędy
        if (this.hasAddBoardErrorsTarget) {
            this.clearErrors(this.addBoardErrorsTarget);
        }

        try {
            const response = await fetch(form.action, {
                method: form.method,
                // WAŻNA ZMIANA: Wysyłaj jako JSON, a nie FormData dla API
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Dodatkowe nagłówek dla Symfony
                },
                body: JSON.stringify({ name: boardName }), // Wysyłaj obiekt JSON z nazwą tablicy
            });

            const data = await response.json();

            if (!response.ok) { // Sprawdź, czy odpowiedź nie jest sukcesem (np. status 4xx, 5xx)
                if (this.hasAddBoardErrorsTarget) {
                    this.displayErrors(this.addBoardErrorsTarget, data.errors || [data.message || 'Wystąpił nieznany błąd.']);
                } else {
                    console.error("Błąd dodawania tablicy:", data);
                }
                return;
            }

            // Sukces
            console.log('Tablica dodana pomyślnie:', data);
            this.closeAddBoardModal();
            window.location.reload(); // Przeładuj stronę, aby nowa tablica była widoczna

        } catch (error) {
            console.error('Error submitting add board form:', error);
            if (this.hasAddBoardErrorsTarget) {
                this.displayErrors(this.addBoardErrorsTarget, ['Wystąpił błąd sieci lub serwera.']);
            }
        }
    }
}