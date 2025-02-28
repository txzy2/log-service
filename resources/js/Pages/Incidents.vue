<script lang="ts" setup>
import {ref} from 'vue';
import axios from 'axios';

import AppLayout from '@/Layouts/AppLayout.vue';
import {genTypes, getSignType} from '../shared/types/types';
import {getSign} from '../shared/utils/generateToken';

const services = ref([]);
const successMessage = ref<{message: string; res: boolean}>({
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
};

getServices();

const saveServiceState = async (service: {
    id: number;
    name: string;
    active: string;
}) => {
    try {
        const params: genTypes = {
            path: 'api/v1/services/edit',
            method: 'POST',
            content: {
                id: service.id,
                name: service.name,
                active: service.active
            }
        };
        const headerParams: getSignType = getSign(params);
        const response = await axios.post(
            params.path,
            params.content,
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
                            id=""
                            v-model="service.active"
                            name=""
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
