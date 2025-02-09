<script setup>
import {ref} from 'vue';

import AppLayout from '@/Layouts/AppLayout.vue';

const incidents = ref([]);
</script>

<template>
  <AppLayout title="Incidents">
    <template #header class="text-black/50 dark:bg-gray dark:text-white/50">
      <h2 class="font-semibold text-xl leading-tight">Инциденты</h2>
    </template>
  </AppLayout>
</template>
