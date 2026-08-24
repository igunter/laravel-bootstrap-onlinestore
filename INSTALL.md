# How To Install

- Click the <b>Use this template</b> button.
- Select the <b>Create a new repository</b> option.
- Give the new repository a name and click the <b>Create repository</b> button.
- Open a terminal window locally.

```
git clone https://github.com/igunter/new-repo-name.git
cd new-repo-name

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

code .
```
