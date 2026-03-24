# ADR 0001: Monorepo, Symfony 8 i React dla MVP Smart Tele-Booking

## Status

Zaakceptowane

## Context

Projekt **Smart Tele-Booking (med-slots-monorepo)** to MVP: lista lekarzy, dostępne sloty, bezpieczna rezerwacja z ochroną przed równoległym podwójnym bookingiem. Zespół ma dostarczyć działający rdzeń szybko, z naciskiem na iterację i kulturę **„Getting things done”**, bez rozdmuchiwania zakresu (admin, pełna autoryzacja poza tym, co konieczne dla demo).

Potrzebujemy spójnej decyzji: **gdzie trzymać kod**, **jak zbudować API i warstwę danych**, **jak zbudować UI** — tak, by ten sam zestaw narzędzi działał od pierwszego dnia do pierwszego wdrożenia.

## Decision

1. **Monorepo** — jedno repozytorium z katalogami `backend/` i `frontend/`, wspólny `docker-compose.yml` w rootcie i dokumentacja ADR w `docs/adr/`.
2. **Backend: PHP 8.4 + Symfony 8** — REST API, Doctrine, Messenger, CQRS/Command-Handler tam, gdzie to ma sens dla mutacji (np. rezerwacja).
3. **Frontend: React (TypeScript, Vite)** — interfejs użytkownika z React Query do stanu serwera.

## Alternatives

| Obszar       | Alternatywa                                      | Dlaczej nie na MVP |
|-------------|---------------------------------------------------|---------------------|
| Repo        | Multi-repo (osobne API + UI)                     | Koszt synchronizacji wersji API/UI, więcej PR-ów i kontekstu |
| Backend     | Node (Nest/Fastify), Go, Laravel                 | Symfony już domuje CQRS, DI, Messenger; inny stack = nowe decyzje i skille |
| Frontend    | Vue, Svelte, „tylko Blade”                        | React + Vite to przewidywalny ekosystem pod komponenty i dane z API |
| Orchestracja| Tylko lokalne PHP/Node bez Dockera               | Trudniejsza powtarzalność środowiska i onboardingu |

## Consequences

- **Pozytywne:** Jeden `docker compose up`, spójne wersje PHP/Node/Postgres, łatwiejsze code review pełnej „ścieżki” funkcji (API + UI), ADR obok kodu.
- **Negatywne:** Monorepo rośnie w rozmiarze; trzeba jasnych granic katalogów (`backend` vs `frontend`). CI może wymagać targetowanych jobów (niekoniecznie od razu).
- **Ryzyko do pilnowania:** Nie mieszać logiki domenowej z kontrolerami „na skróty” — konwencje Symfony/CQRS utrzymają monolit czytelnym.

## 5x why (łańcuch uzasadnień)

1. **Dlaczego monorepo?** Żeby jedna zmiana (np. kontrakt `POST /api/slots/book`) była widoczna obok klienta w jednym PR i jednym środowisku compose.
2. **Dlaczego to przyspiesza iterację?** Bo znika narzut na wersjonowanie dwóch repozytoriów, tagów i „która wersja frontu pasuje do którego API”.
3. **Dlaczego iteracja ma pierwszeństwo?** Bo MVP ocenia rynek i flow rezerwacji — szybka pętla „pomysł → kod → demo” jest ważniejsza niż idealna separacja organizacyjna na start.
4. **Dlaczego Symfony 8 po stronie API?** Bo daje solidny rad MVC, Doctrine, transakcje, blokady pesymistyczne na encjach i Messenger do pracy w tle — to bezpośrednio wspiera wymaganie bezpiecznej rezerwacji i asynchronicznych powiadomień bez wymyślania kolejki od zera.
5. **Dlaczego React z Vite?** Bo oddziela UI od API (jasny kontrakt REST), a Vite/React Query skracają czas od podłączenia endpointów do działającego ekranu — czyli znowu **„Getting things done”** przy ograniczonym zakresie.

**Skrót:** Monorepo skraca dystans między warstwami, Symfony daje dojrzałe narzędzia pod trwałość danych i współbieżność, React daje szybkie UI na read-only listach i mutacji — wszystko w jednym, powtarzalnym Dockerze.

## Open questions

- Czy w CI uruchamiamy na starcie tylko testy `backend/`, czy też `lint`/`build` frontendu — do ustalenia przy pierwszym pipeline.
- Docelowy reverse proxy (nginx/Caddy) przed PHP-FPM w produkcji — poza zakresem tego ADR; lokalnie HTTP dla backendu może być serwowane przez wbudowany serwer PHP w kontenerze deweloperskim.
