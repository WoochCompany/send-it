<script setup lang="ts">
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

interface ChartData {
    status: string;
    count: number;
}

interface Props {
    data: ChartData[];
}

const props = defineProps<Props>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'sent':
            return 'rgba(34, 197, 94, 0.8)';
        case 'pending':
            return 'rgba(234, 179, 8, 0.8)';
        case 'scheduled':
            return 'rgba(59, 130, 246, 0.8)';
        case 'failed':
            return 'rgba(239, 68, 68, 0.8)';
        default:
            return 'rgba(156, 163, 175, 0.8)';
    }
};

const chartData = computed(() => ({
    labels: props.data.map((item) => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
    datasets: [
        {
            data: props.data.map((item) => item.count),
            backgroundColor: props.data.map((item) => getStatusColor(item.status)),
            borderColor: props.data.map((item) => getStatusColor(item.status).replace('0.8', '1')),
            borderWidth: 2,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                padding: 20,
                usePointStyle: true,
            },
        },
    },
}));
</script>

<template>
    <div class="h-64">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
