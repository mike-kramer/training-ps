# GitHub Actions: настройка до первого пуша

Workflow: `.github/workflows/deploy.yml`. Команды деплоя записаны прямо в этом файле и уходят на сервер по SSH. Отдельный скрипт на сервер класть не нужно.

Пуш этого workflow в ветку `master` сразу запускает прогон. Секреты и доступ на сервер нужно сделать **до** этого пуша. Иначе job с тестами всё равно отработает и потратит минуты, а деплой упадёт на SSH.

Ручная кнопка **Actions → Test and deploy → Run workflow** появляется только после того, как файл workflow уже есть на `master`. Первый запуск — сам пуш.

## Что делает прогон

1. На runner GitHub поднимается dev-стек `docker/` (Postgres со схемой `test_schema`, Redis, PHP) и выполняется `php artisan test --testsuite=Feature`.
2. Если тесты прошли и ветка — `master`, runner заходит на сервер по SSH и там выполняет две команды из workflow: `git pull --ff-only` в `/var/www/html/training-ps`, затем в `server-docker` команду `docker compose up -d --build php horizon scheduler nginx`.
3. Код попадает в том `app_code` при старте контейнера `php`. `application.env` и Certbot не трогаются. Файла `deploy.sh` на сервере нет и для первого pull он не нужен.

Каждый пуш в `master` запускает и тесты, и деплой. Текущий прогон следующий пуш не отменяет.

## Два разных ключа

| Ключ | Куда | Зачем |
|------|------|--------|
| Новый, из этой инструкции | Секрет `SSH_PRIVATE_KEY` в GitHub, публичная часть на сервере | GitHub заходит на сервер |
| Уже лежащий на сервере у `git` | Не класть в секреты GitHub | Сервер делает `git pull` |

Приватный ключ от GitHub-аккаунта и ключ клона репозитория для этого не подходят.

## 1. Пользователь на сервере

Пользователь, под которым зайдёт Actions:

- владеет каталогом `/var/www/html/training-ps` (клон, из которого уже поднят `server-docker`);
- может выполнить `docker` и `docker compose` без `sudo`;
- из этого каталога `git pull --ff-only` проходит без запроса пароля или токена.

Проверка на сервере под этим пользователем:

```bash
id
docker compose version
cd /var/www/html/training-ps
git pull --ff-only
cd server-docker
docker compose ps
```

Если `docker` пишет permission denied:

```bash
sudo usermod -aG docker "$USER"
```

Новая группа действует после повторного входа по SSH.

Если `git pull` спрашивает логин, настройте доступ клона отдельно (SSH-ключ самого сервера к GitHub или сохранённый credential helper). Пока pull интерактивный, деплой зависнет и исчерпает минуты job.

## 2. Ключ для входа GitHub на сервер

На своей машине, не на сервере:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/training-ps-deploy -C "github-actions-deploy" -N ""
```

Появится два файла:

- `~/.ssh/training-ps-deploy` — приватный, он пойдёт в секрет GitHub;
- `~/.ssh/training-ps-deploy.pub` — публичный, его нужно прописать на сервере.

Публичный ключ пользователю с шага 1:

```bash
ssh-copy-id -i ~/.ssh/training-ps-deploy.pub USER@SERVER
```

Если `ssh-copy-id` нет, одной строкой содержимое `.pub` дописывается в `~/.ssh/authorized_keys` этого пользователя. Каталог `~/.ssh` должен быть `700`, файл `authorized_keys` — `600`.

Проверка с вашей машины (в сессии не должен спросить пароль):

```bash
ssh -i ~/.ssh/training-ps-deploy USER@SERVER 'docker compose version && test -d /var/www/html/training-ps'
```

Приватный файл не коммитить и не класть в `authorized_keys`.

## 3. Секреты репозитория

На GitHub: репозиторий → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**.

| Имя | Значение |
|-----|----------|
| `SSH_HOST` | IP или DNS сервера, без `user@` и без `https://` |
| `SSH_USER` | Linux-пользователь с шага 1 |
| `SSH_PRIVATE_KEY` | Весь файл `~/.ssh/training-ps-deploy`, включая строки `BEGIN OPENSSH PRIVATE KEY` и `END OPENSSH PRIVATE KEY` |
| `SSH_PORT` | Не создавать, если SSH на порту 22. Иначе номер порта |

Имена секретов регистрозависимые и должны совпасть с таблицей.

`SSH_PRIVATE_KEY` удобно вставить так:

```bash
cat ~/.ssh/training-ps-deploy
```

Скопировать весь вывод, вместе с пустой строкой в конце файла.

## 4. Actions включены, оплата сверх квоты выключена

**Settings → Actions → General:** разрешены запуски workflow (для своего репозитория достаточно варианта по умолчанию, allow actions).

**Settings → Billing and plans → Spending limits:** лимит GitHub Actions поставьте **$0**. Тогда при исчерпании бесплатных минут job остановится и счёт не выставится.

Минуты (оценки одного полного прогона — 25–45 минут):

- публичный репозиторий и стандартный `ubuntu-latest` — без месячного потолка минут;
- приватный репозиторий на GitHub Free — 2000 минут в месяц, примерно 45–80 таких прогонов;
- минуты пишутся и за упавший прогон, пока job работал;
- larger runner’ы в этом workflow не используются и всегда платные.

## 5. Пуш

После шагов 1–4 можно коммитить и пушить в `master`. Сразу после пуша во вкладке **Actions** появится **Test and deploy**.

Деплой начинается только после зелёных feature-тестов. Таймаут каждого job — 40 минут.
