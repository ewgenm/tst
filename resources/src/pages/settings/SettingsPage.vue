<!-- SettingsPage -->
<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">⚙️ Настройки</h1>
    <div class="flex gap-6">
      <nav class="w-48 space-y-2">
        <router-link to="/settings/profile" class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Профиль</router-link>
        <router-link to="/settings/integrations" class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Интеграции</router-link>
      </nav>
      <div class="flex-1"><router-view /></div>
    </div>
  </div>
</template>
