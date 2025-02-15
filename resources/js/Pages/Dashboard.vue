<script setup>
import {ref} from 'vue';
import axios from 'axios';
import CryptoJS from 'crypto-js';

import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';

import {generateToken, generateServicesToken} from '@/shared/utils/generateToken';

const checkData = ref({
  date: null,
  service: null
});

const serverData = ref({
  count: null,
  date: null,
  incident_object: null,
  incident_text: null,
  source: null
});

const error = ref(null);
const toastOpen = ref(false);
const loading = ref(false);

const exportLogs = async () => {
  try {
    const timestamp = Math.floor(Date.now() / 1000);
    const signature = await generateServicesToken(timestamp);

    const data = {
      ...(checkData.value.date && { date: checkData.value.date }),
      ...(checkData.value.service && { service: checkData.value.service })
    };

    console.log(data);

    const response = await axios.post('/api/v1/log/export', data, {
      responseType: 'blob',
      headers: {
        'X-Timestamp': timestamp,
        'X-Signature': signature
      }
    });
    
    // Получаем имя файла из заголовка ответа
    const filename = response.headers['content-disposition']
      ? response.headers['content-disposition'].split('filename=')[1].replace(/"/g, '')
      : `logs_${new Date().toISOString().split('T')[0]}.csv`;

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Ошибка при экспорте:', error);
  }
}

const hashHmac = () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const key = import.meta.env.VITE_SERVICES_TOKEN;
  const hmac = CryptoJS.HmacSHA256(timestamp, key);
  return CryptoJS.enc.Hex.stringify(hmac);
}

// TODO: Переделать логику отправки ошибки
const fetchData = async () => {
  try {
    const token = await generateToken({
      service: checkData.value.service
    });

    const data = {
      service: checkData.value.service,
      token: token,
      ...(checkData.value.date && {date: checkData.value.date})
    };
    const response = await axios.post('/api/v1/log/report', data);

    error.value = null;
    if (response.data.message && response.data.message.length > 0) {
      const responseData = response.data.message[0];
      serverData.value = {
        count: responseData.count,
        date: responseData.date,
        incident_object: responseData.incident_object,
        incident_text: responseData.incident_text,
        source: responseData.source
      };
    }

    console.log('Обновленный checkData:', checkData.value);
  } catch (err) {
    error.value = err.response?.data?.message
      ? err.response.data.message
      : 'Произошла ошибка при выполнении запроса';
  }
};

const resetServerData = () => {
  serverData.value = {
    count: null,
    incident_object: null,
    incident_text: null,
    source: null
  };
};

const handleSubmit = async () => {
  loading.value = true;
  resetServerData();
  await fetchData();
  loading.value = false;
};
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header class="text-black/50 dark:bg-gray dark:text-white/50">
      <h2 class="font-semibold text-xl leading-tight">Мониторинг</h2>
    </template>

    <div class="py-6">
      <div class="max-w-[95%] flex flex-col gap-4 mx-auto sm:px-6 lg:px-8">
        <form
          @submit.prevent="handleSubmit"
          class="w-1/2 flex flex-col m-auto items-start gap-4"
        >
          <div class="w-full flex gap-4">
            <div class="w-1/2">
              <code for="date" class="block text-[16px] font-bold"> Дата </code>
              <input
                type="date"
                id="date"
                v-model="checkData.date"
                class="w-full py-2 text-black mt-1 block rounded-md shadow-sm focus:-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>
            <div class="w-1/2">
              <code for="service" class="block text-[16px] font-bold">
                Сервис
              </code>
              <select
                id="service"
                v-model="checkData.service"
                class="w-full text-black mt-1 block rounded-md shadow-sm focus:-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="WSPG">WSPG</option>
                <option value="ADS">ADS</option>
              </select>
            </div>
          </div>
        </form>

        <div class="flex items-center justify-center gap-4 mt-4">
            <button
              type="button"
              class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
              :disabled="loading"
              @click="handleSubmit"
            >
              {{ loading ? 'Загрузка...' : 'Получить данные' }}
            </button>

            <button @click="exportLogs">Экспорт в CSV</button>

            
        </div>

        <div v-if="error" class="text-center font-bold text-red-500 text-[13px]">
            {{ error }}
        </div>

        <div>
          <code class="text-[18px] font-bold text-black"> Статистика </code>

          <table class="w-full mt-4">
            <thead>
              <tr>
                <th class="p-2 text-[16px] font-bold">
                  <code>Сервис</code>
                </th>
                <th class="p-2 text-[16px] font-bold">
                  <code>Дата</code>
                </th>
                <th class="p-2 text-[16px] font-bold">
                  <code>Количество повторений</code>
                </th>
                <th class="p-2 text-[16px] font-bold">
                  <code>Объект инцидента</code>
                </th>
                <th class="p-2 text-[16px] font-bold">
                  <code>Текст инцидента</code>
                </th>
                <th class="p-2 text-[16px] font-bold">
                  <code>Источник</code>
                </th>
              </tr>
            </thead>
            <tbody class="text-black">
              <tr
                class="cursor-pointer group border-b border-gray-200 hover:bg-gray-100"
              >
                <td class="p-2 text-center group-hover:rounded-l-lg">
                  <span class="font-semibold">{{
                    checkData.service || '-'
                  }}</span>
                </td>
                <td class="p-2 text-center">
                  <span class="font-semibold">{{
                    serverData.date || '-'
                  }}</span>
                </td>
                <td class="p-2 text-center">
                  {{ serverData.count || '-' }}
                </td>
                <td class="p-2 text-center">
                  {{ serverData.incident_object || '-' }}
                </td>
                <td class="p-2 text-center">
                  {{ serverData.incident_text || '-' }}
                </td>
                <td class="p-2 text-center group-hover:rounded-r-lg">
                  {{ serverData.source || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- <div v-else class="text-green-500">Данные успешно загружены</div> -->
  </AppLayout>
</template>

<style scoped>
td {
  font-size: 14px;
}
</style>
