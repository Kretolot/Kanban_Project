// assets/controllers/modal_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["modal", "form", "colIdInput", "editModal", "editForm", "editTaskId", "editTitle", "editDescription", "addBoardModal", "addBoardForm"]
    
    connect() {
        // Dodajemy event listener do zamykania modala po kliknięciu poza nim
        this.modalTarget.addEventListener('click', this.closeOnBackdrop.bind(this));
        this.editModalTarget.addEventListener('click', this.closeOnBackdrop.bind(this));
        
        // Sprawdź, czy addBoardModal istnieje przed dodaniem listenera (jest w base.html.twig)
        if (this.hasAddBoardModalTarget) {
            this.addBoardModalTarget.addEventListener('click', this.closeOnBackdrop.bind(this));
        }

        // Dodaj event listenery do kliknięcia na karty zadań, aby otworzyć modal edycji
        // Sprawdź, czy element zawiera targety kart (jest tylko na stronie board)
        if (this.element.hasAttribute('data-drag-drop-target')) { // Sprawdzamy czy to element board
            this.element.querySelectorAll('[data-drag-drop-target="card"]').forEach(card => {
                card.addEventListener('click', this.openEditModal.bind(this));
            });
        }
    }

    // Modal do dodawania zadań
    open(event) {
        const colId = event.target.dataset.colId;
        this.colIdInputTarget.value = colId;

        this.modalTarget.classList.remove('hidden');
        this.modalTarget.classList.add('flex');

        const firstInput = this.formTarget.querySelector('input[type="text"]');
        if (firstInput) {
            firstInput.focus();
        }
    }

    close() {
        this.modalTarget.classList.add('hidden');
        this.modalTarget.classList.remove('flex');
        this.formTarget.reset();
    }

    submitForm(event) {
        event.preventDefault();

        const formData = new FormData(this.formTarget);

        fetch(this.formTarget.action, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                this.close();
                location.reload();
            } else {
                console.error('Błąd podczas dodawania zadania');
            }
        }).catch(error => {
            console.error('Błąd:', error);
        });
    }

    // Modal do edycji/usuwania zadań
    openEditModal(event) {
        const card = event.currentTarget;
        const taskId = card.dataset.taskId;
        const title = card.querySelector('h4').innerText;
        const descriptionElement = card.querySelector('p');
        const description = descriptionElement ? descriptionElement.innerText : '';

        this.editTaskIdTarget.value = taskId;
        this.editTitleTarget.value = title;
        this.editDescriptionTarget.value = description;

        // Ustawiamy action formularza na odpowiednią ścieżkę do edycji
        this.editFormTarget.action = `/task/${taskId}/edit`;

        this.editModalTarget.classList.remove('hidden');
        this.editModalTarget.classList.add('flex');
    }

    closeEditModal() {
        this.editModalTarget.classList.add('hidden');
        this.editModalTarget.classList.remove('flex');
        this.editFormTarget.reset();
    }

    submitEditForm(event) {
        event.preventDefault();

        const formData = new FormData(this.editFormTarget);
        const taskId = this.editTaskIdTarget.value;

        fetch(this.editFormTarget.action, {
            method: 'POST', // Zmieniamy na POST, ponieważ Symfony rozpoznaje _method=PUT
            body: formData
        }).then(response => {
            if (response.ok) {
                this.closeEditModal();
                location.reload();
            } else {
                console.error('Błąd podczas edycji zadania');
            }
        }).catch(error => {
            console.error('Błąd:', error);
        });
    }

    async deleteTask() {
        const taskId = this.editTaskIdTarget.value;
        if (!confirm(`Czy na pewno chcesz usunąć zadanie (ID: ${taskId})?`)) {
            return;
        }

        try {
            const response = await fetch(`/task/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            if (response.ok) {
                this.closeEditModal();
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    location.reload();
                }
            } else {
                console.error('Błąd podczas usuwania zadania:', data.error || 'Nieznany błąd');
                alert(`Błąd podczas usuwania zadania: ${data.error || 'Nieznany błąd'}`);
            }
        } catch (error) {
            console.error('Błąd:', error);
            alert('Wystąpił błąd podczas usuwania zadania.');
        }
    }

    // Modal do dodawania nowych tablic
    openAddBoardModal() {
        this.addBoardModalTarget.classList.remove('hidden');
        this.addBoardModalTarget.classList.add('flex');
        const firstInput = this.addBoardFormTarget.querySelector('input[type="text"]');
        if (firstInput) {
            firstInput.focus();
        }
    }

    closeAddBoardModal() {
        this.addBoardModalTarget.classList.add('hidden');
        this.addBoardModalTarget.classList.remove('flex');
        this.addBoardFormTarget.reset();
    }

    submitAddBoardForm(event) {
        event.preventDefault();

        const formData = new FormData(this.addBoardFormTarget);

        fetch(this.addBoardFormTarget.action, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (response.ok) {
                this.closeAddBoardModal();
                location.reload(); // Odśwież stronę główną, aby zobaczyć nową tablicę
            } else {
                console.error('Błąd podczas dodawania tablicy');
                response.json().then(data => {
                    alert(`Błąd: ${data.error}`);
                }).catch(() => alert('Wystąpił błąd podczas dodawania tablicy.'));
            }
        }).catch(error => {
            console.error('Błąd:', error);
            alert('Wystąpił błąd sieci podczas dodawania tablicy.');
        });
    }

    // Funkcja do usuwania tablicy
    async deleteBoard(event) {
        const boardId = event.currentTarget.dataset.boardId;
        const boardName = event.currentTarget.dataset.boardName;

        if (!confirm(`Czy na pewno chcesz usunąć tablicę "${boardName}" (ID: ${boardId})? To spowoduje usunięcie wszystkich list i zadań z nią związanych.`)) {
            return;
        }

        try {
            const response = await fetch(`/board/${boardId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            if (response.ok) {
                if (data.redirect) {
                    window.location.href = data.redirect; // Przekieruj na stronę główną
                } else {
                    location.reload();
                }
            } else {
                console.error('Błąd podczas usuwania tablicy:', data.error || 'Nieznany błąd');
                alert(`Błąd podczas usuwania tablicy: ${data.error || 'Nieznany błąd'}`);
            }
        } catch (error) {
            console.error('Błąd:', error);
            alert('Wystąpił błąd podczas usuwania tablicy.');
        }
    }

    // Zamykanie modala po kliknięciu tła
    closeOnBackdrop(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
        if (event.target === this.editModalTarget) {
            this.closeEditModal();
        }
        if (this.hasAddBoardModalTarget && event.target === this.addBoardModalTarget) {
            this.closeAddBoardModal();
        }
    }
}