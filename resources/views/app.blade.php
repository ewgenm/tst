<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'TaskSync Team') }}</title>
    @vite(['resources/css/app.css', 'resources/src/main.ts'])
  </head>
  <body class="bg-gray-50 dark:bg-gray-900">
    <div id="app"></div>
  </body>
</html>
