<script lang="ts" setup>
import {ref} from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

import {genTypes, getSignType} from '../shared/types/types';
import {getSign} from '../shared/utils/generateToken';

const checkData = ref<{date: string; service: string}>({
    date: null,
    service: null
});

const serverData = ref<{
    service: string;
    date: string;
    count: number;
    incident_object: string;
    incident_text: string;
    source: string;
}>({
    count: null,
    date: null,
    incident_object: null,
    incident_text: null,
    source: null
});

const error = ref<string>(null);
const loading = ref<boolean>(false);

const exportLogs = async () => {
    try {

        const params: genTypes = {
            path: 'api/v1/log/export',
            method: 'POST',
            content: JSON.stringify({
                ...(checkData.value.date && {date: checkData.value.date}),
                ...(checkData.value.service && {service: checkData.value.service})
            })
        };

        const headerParams: getSignType = getSign(params);
        const response = await axios.post(params.path, JSON.parse(params.content), {
            responseType: 'blob',
            headers: {
                'x-timestamp': headerParams.timestamp,
                'x-signature': headerParams.signature
            }
        });
        // Получаем имя файла из заголовка ответа
        const filename = response.headers['content-disposition']
            ? response.headers['content-disposition']
                .split('filename=')[1]
                .replace(/"/g, '')
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
};

const fetchData = async () => {
    const params: genTypes = {
        path: 'api/v1/log/report',
        method: 'POST',
        content: {
            service: checkData.value.service,
            date: checkData.value.date
        }
    };
    const headerParams: getSignType = getSign(params);
    const response = await axios.post(params.path,
        params.content,
        {
            headers: {
                'x-timestamp': headerParams.timestamp,
                'x-signature': headerParams.signature
            }
        }
    );

    error.value = null;
    if (!response.data.success) {
        error.value = response.data.message;
        return;
    }

    if (
        Array.isArray(response.data.data) &&
        response.data.data.length > 0
    ) {
        // Обработка массива сообщений
        const responseData = response.data.data.map(item => ({
            count: item.count,
            service: item.service,
            date: item.date,
            incident_object: item.incident_object,
            incident_text: item.incident_text,
            source: item.source
        }));
        serverData.value = responseData; // Сохраняем массив данных
    }

    console.log('Обновленный checkData:', checkData.value);

};

const resetServerData = () => {
    serverData.value = {
        service: null,
        date: null,
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
            <div class="flex flex-col gap-4 mx-auto sm:px-6 lg:px-8">
                <div class="flex">
                    <iframe
                        frameborder="0"
                        height="300"
                        src="http://localhost:4000/d-solo/bed652absrsaoa/incident?orgId=1&from=1735678800000&to=1767214799999&timezone=browser&theme=light&panelId=1&__feature.dashboardSceneSolo"
                        width="650"
                    ></iframe>

                    <form
                        class="w-1/2 flex flex-col m-auto items-start gap-4"
                        @submit.prevent="handleSubmit"
                    >
                        <div class="w-full flex gap-4">
                            <div class="w-1/2">
                                <code class="block text-[16px] font-bold"
                                      for="date">
                                    Дата
                                </code>
                                <input
                                    id="date"
                                    v-model="checkData.date"
                                    class="w-full py-2 text-black mt-1 block rounded-md shadow-sm focus:-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    type="date"
                                />
                            </div>
                            <div class="w-1/2">
                                <code class="block text-[16px] font-bold"
                                      for="service">
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

                        <div
                            class="flex items-center justify-center gap-4 mt-4">
                            <button
                                :disabled="loading || !checkData.service"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
                                type="button"
                                @click="handleSubmit"
                            >
                                {{ loading ? 'Загрузка...' : 'Получить данные'
                                }}
                            </button>

                            <button
                                class="px-4 py-2 bg-gray-300 text-black rounded-md hover:bg-gray-100 disabled:opacity-50"
                                @click="exportLogs">Экспорт в CSV
                            </button>


                        </div>

                        <div
                            v-if="error"
                            class="text-center text-red-500 text-[16px]"
                        >
                            <span class="font-bold">ERROR:</span> {{ error }}
                        </div>
                    </form>
                </div>

                <div>
                    <code class="text-[18px] font-bold text-black">
                        Статистика </code>

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
                        <tbody v-if="serverData.length > 0" class="text-black">
                        <tr
                            v-for="(item, index) in serverData"
                            :key="index"
                            class="cursor-pointer group border-b border-gray-200 hover:bg-gray-100"
                        >
                            <td class="p-2 text-center group-hover:rounded-l-lg">
                                <span
                                    class="font-semibold">{{ item?.service || '-'
                                    }}</span>
                            </td>
                            <td class="p-2 text-center">
                                <span class="font-semibold">{{ item?.date || '-'
                                    }}</span>
                            </td>
                            <td class="p-2 text-center">
                                {{ item?.count || '-' }}
                            </td>
                            <td class="p-2 text-center">
                                {{ item?.incident_object || '-' }}
                            </td>
                            <td class="p-2 text-center">
                                {{ item?.incident_text || '-' }}
                            </td>
                            <td class="p-2 text-center group-hover:rounded-r-lg">
                                {{ item?.source || '-' }}
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
