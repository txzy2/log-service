<script setup>
import {ref} from 'vue';
import axios from 'axios';
import { generateServicesToken} from '@/shared/utils/generateToken';

import AppLayout from '@/Layouts/AppLayout.vue';

const services = ref([]);

const getServices = async () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const signature = await generateServicesToken(timestamp);

  const response = await axios.get('/api/v1/services', {
    headers: {
      'X-Timestamp': timestamp,
      'X-Signature': signature
    }
  });

  console.log(response.data);
  services.value = response.data.data;
};

getServices();
</script>

<template>
  <AppLayout title="Incidents">
    <template #header class="text-black/50 dark:bg-gray dark:text-white/50">
      <h2 class="font-semibold text-xl leading-tight">Сервисы</h2>
    </template>

    <div class="w-full flex items-center justify-center">
      <table class="">
        <thead>
          <tr>
            <th>ID</th>
            <th>Сервис</th>
            <th>Активность</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="service in services" :key="service.id">
            <td>{{ service.id }}</td>
            <td>{{ service.name }}</td>
            <td>{{ service.active }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<style scoped>

</style>

