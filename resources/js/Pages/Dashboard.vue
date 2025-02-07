<script setup>
import {ref} from 'vue';
import axios from 'axios';

import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';

import {generateToken} from '@/shared/utils/generateToken';

const checkData = ref({
  date: null,
  service: null,
  count: null,
  incident_object: null,
  incident_text: null,
  source: null
});
const error = ref(null);

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
    const response = await axios.post('/api/v1/report', data);

    error.value = null;
    // Берем первый элемент из массива message
    if (response.data.message && response.data.message.length > 0) {
      checkData.value = {
        ...checkData.value,
        ...response.data.message[0] // Используем первый элемент массива
      };
    }

    console.log('Обновленный checkData:', checkData.value);
  } catch (err) {
    if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Произошла ошибка при выполнении запроса';
    }
  }
};
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header class="text-black/50 dark:bg-gray dark:text-white/50">
      <h2 class="font-semibold text-xl leading-tight">Dashboard</h2>
    </template>

    <div class="py-6">
      <div class="max-w-[95%] flex flex-col gap-4 mx-auto sm:px-6 lg:px-8">
        <form
          @submit.prevent="fetchData"
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

          <button
            type="submit"
            class="w-[200px] ml-auto bg-blue-500 text-white py-2 rounded-md"
          >
            Получить данные
          </button>
        </form>

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
                  {{ checkData.service }}
                </td>
                <td class="p-2 text-center">
                  <span v-if="checkData.date">{{ checkData.date }}</span>
                  <span v-else-if="checkData.service">x</span>
                </td>
                <td class="p-2 text-center">
                  {{ checkData.count }}
                </td>
                <td class="p-2 text-center">
                  {{ checkData.incident_object }}
                </td>
                <td class="p-2 text-center">
                  {{ checkData.incident_text }}
                </td>
                <td class="p-2 text-center group-hover:rounded-r-lg">
                  {{ checkData.source }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
td {
  font-size: 14px;
}
</style>
