<script lang="ts" setup>
import { ref } from 'vue';
import axios from 'axios';
import { Trash2, Plus } from 'lucide-vue-next';

import AppLayout from '@/Layouts/AppLayout.vue';
import { genTypes, getSignType } from '../shared/types/types';
import { getSign } from '../shared/utils/generateToken';

const services = ref([]);
const successMessage = ref<{ message: string; res: boolean }>({
    message: '',
    res: false
});

const getServices = async () => {
    const params: genTypes = {
        path: 'api/v1/services',
        method: 'GET',
        content: ''
    };
    const headerParams: getSignType = getSign(params);
    const response = await axios.get(params.path, {
        headers: {
            'X-Timestamp': headerParams.timestamp,
            'X-Signature': headerParams.signature
        }
    });

    services.value = response.data.data;
    console.log(services.value);
};

getServices();

const saveServiceState = async (service: {
    id: number;
    name: string;
    active: string;
}) => {
    if (confirm('Вы действительно хотите изменить активность сервиса?')) {
        try {
            const params: genTypes = {
                path: 'api/v1/incidents/services/edit',
                method: 'POST',
                content: {
                    name: service.name,
                    active: service.active
                }
            };
            const headerParams: getSignType = getSign(params);
            const response = await axios.post(params.path, params.content, {
                headers: {
                    'x-timestamp': headerParams.timestamp,
                    'x-signature': headerParams.signature
                }
            });

            successMessage.value = response.data.success
                ? { message: 'Состояние сервиса успешно сохранено!', res: true }
                : { message: 'Ошибка при сохранении состояния сервиса.', res: false };
        } catch (error) {
            successMessage.value = {
                message: 'Произошла ошибка при сохранении.',
                res: false
            };
        }
    }
};

const deleteService = async (service: string) => {
    if (confirm('Вы действительно хотите удалить сервис?')) {
        const params: genTypes = {
            path: 'api/v1/incidents/services/delete',
            method: 'POST',
            content: {
                service
            }
        };

        const headerParams: getSignType = getSign(params);
        const response = await axios.post(params.path, params.content, {
            headers: {
                'x-timestamp': headerParams.timestamp,
                'x-signature': headerParams.signature
            }
        });

        if (!response.data.success) {
            alert('Произошла ошибка при удалении сервиса.');
        }

        services.value = response.data.data;
    }
};
</script>

<template>
    <AppLayout title="Incidents">
        <template #header>
            <h2 class="font-semibold text-xl leading-tight">Сервисы</h2>
        </template>
        <div class="w-[70%] flex flex-col m-auto gap-4">
            <div class="table-container">
                <code class="font-bold text-[18px]">Активность сервисов</code>
                <table class="grid-table">
                    <thead>
                        <tr>
                            <th>Сервис</th>
                            <th>Активность</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="service in services.services" :key="service.id">
                            <td>{{ service.name }}</td>
                            <td>
                                <select id="" v-model="service.active"
                                    class="cursor-pointer transition-all duration-150 hover:scale-110" name=""
                                    @change="saveServiceState(service)">
                                    <option value="Y">Y</option>
                                    <option class="option" value="N">N</option>
                                </select>
                            </td>
                            <td class="cursor-pointer">
                                <Trash2 @click="deleteService(service.name)" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <div class="flex items-center gap-2">
                    <code class="font-bold text-[18px]">Отслеживаемые ошибки</code>
                    <button class="p-1 bg-gray-200 rounded-md transition-all duaration-150 hover:scale-110"
                        type="button" title="Добавить ошибку">
                        <Plus size="16" />
                    </button>
                </div>
                <table class="grid-table">
                    <thead>
                        <tr class="flex items-center gap-5">
                            <th class="">TYPE_NAME</th>
                            <th>CODE</th>
                            <th>LIFECYCLE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in services.incidentTypes" :key="item.name">
                            <td>{{ item.type_name }}</td>
                            <td>{{ item.code }}</td>
                            <td>{{ item.lifecycle }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

.table-container {
    overflow: hidden;
}

.grid-table {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
}

.grid-table thead,
.grid-table tbody,
.grid-table tr {
    display: contents;
}

.grid-table th,
.grid-table td {
    text-align: center;
}

.grid-table th {
    background-color: #f4f4f4;
    font-weight: bold;
    font-size: 16px;
}

.grid-table td {
    font-size: 14px;
}
</style>
