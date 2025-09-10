# Kanban Project

## Opis projektu

Kanban Project to zaawansowana aplikacja webowa do zarządzania zadaniami, oparta na metodologii Kanban. 
Pozwala użytkownikom na tworzenie tablic, kolumn i zadań, które można łatwo przenosić i organizować.

## Funkcje

- Autoryzacja użytkowników (rejestracja, logowanie)
- Tworzenie wielu tablic Kanban
- Dodawanie kolumn do tablic
- Zarządzanie zadaniami (dodawanie, edycja, usuwanie)
- Drag & drop zadań między kolumnami
- Przenoszenie kolumn
- Zapis danych w bazie SQLite

## Wymagania techniczne

- PHP 8.2+
- Composer
- Symfony 7.3
- SQLite
- Node.js (do assets)

## Instalacja

1. Sklonuj repozytorium:
```bash
git clone [link do repozytorium]
cd kanban-projekt
```

2. Zainstaluj zależności:
```bash
composer install
```

3. Skonfiguruj bazę danych:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4. Uruchom serwer:
```bash
symfony server:start
```

5. Otwórz w przeglądarce:
```
http://127.0.0.1:8000
```

## Technologie

- Backend: Symfony 7.3
- Frontend: Stimulus.js, Tailwind CSS
- Baza danych: SQLite
- Autentykacja: Symfony Security
- Walidacja: Symfony Validator



## Struktura projektu

```
├── assets/           # Pliki JavaScript i CSS
├── config/           # Konfiguracja Symfony
├── migrations/       # Migracje bazy danych
├── public/           # Publiczny katalog www
├── src/              # Główny kod źródłowy
│   ├── Controller/   # Kontrolery
│   ├── Entity/       # Modele danych
│   ├── Repository/   # Repozytoria
│   └── Service/      # Usługi biznesowe
└── templates/        # Szablony Twig
```

## Autorzy

- Tomasz Gradowski
