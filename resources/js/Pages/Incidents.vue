<script setup lang="ts">
import {ref} from 'vue';
import axios from 'axios';
import {generateServicesToken} from '@/shared/utils/generateToken';

import AppLayout from '@/Layouts/AppLayout.vue';
import {getSignType} from '../shared/types/types';
import {getSign} from '../shared/utils/generateToken';

const services = ref([]);
const successMessage = ref<{message: string; res: boolean}>({
  message: '',
  res: false
});

const getServices = async () => {
  const headerParams: getSignType = await getSign();
  const response = await axios.get('/api/v1/services', {
    headers: {
      'X-Timestamp': headerParams.timestamp,
      'X-Signature': headerParams.signature
    }
  });
  services.value = response.data.data;
};

getServices();

const saveServiceState = async (service: {
  id: number;
  name: string;
  active: string;
}) => {
  try {
    const headerParams: getSignType = await getSign();
    const response = await axios.post(
      '/api/v1/services/edit',
      {
        id: service.id,
        name: service.name,
        active: service.active
      },
      {
        headers: {
          'x-timestamp': headerParams.timestamp,
          'x-signature': headerParams.signature
        }
      }
    );

    successMessage.value = response.data.success
      ? {message: 'Состояние сервиса успешно сохранено!', res: true}
      : {message: 'Ошибка при сохранении состояния сервиса.', res: false};
  } catch (error) {
    successMessage.value = {message: 'Произошла ошибка при сохранении.', res: false};
  }
};
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
            <td>
              <select
                name=""
                id=""
                v-model="service.active"
                @change="saveServiceState(service)"
              >
                <option value="Y">Y</option>
                <option class="option" value="N">N</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<style scoped>
select {
  height: 40px;
  font-size: 14px;
  font-weight: bold;

  border: none;
  outline: none;
}
</style>
