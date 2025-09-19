<script setup lang="ts">
import { BarElement, CategoryScale, Chart as ChartJS, Filler, Legend, LinearScale, LineElement, PointElement, Title, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

interface TimeSlot {
    time: string;
    count: number;
    timestamp: string;
}

interface ProviderData {
    provider_name: string;
    rate_limit: number;
    time_slots: TimeSlot[];
}

interface Props {
    data: ProviderData[];
}

const props = defineProps<Props>();

const generateColor = (index: number) => {
    const colors = [
        'rgba(59, 130, 246, 1)', // blue
        'rgba(34, 197, 94, 1)', // green
        'rgba(239, 68, 68, 1)', // red
        'rgba(234, 179, 8, 1)', // yellow
        'rgba(147, 51, 234, 1)', // purple
        'rgba(245, 101, 101, 1)', // orange
    ];
    return colors[index % colors.length];
};

const chartData = computed(() => {
    if (!props.data.length) {
        return {
            labels: [],
            datasets: [],
        };
    }

    // Utiliser les time slots du premier provider comme labels
    const labels = props.data[0]?.time_slots.map((slot) => slot.time) || [];

    const datasets = [];

    // Créer un dataset pour chaque provider
    props.data.forEach((provider, index) => {
        const color = generateColor(index);

        // Dataset pour les emails programmés (line chart)
        datasets.push({
            label: `${provider.provider_name} - Scheduled`,
            type: 'line' as const,
            data: provider.time_slots.map((slot) => slot.count),
            borderColor: color,
            backgroundColor: color.replace('1)', '0.1)'),
            borderWidth: 2,
            fill: false,
            tension: 0.3,
            pointBackgroundColor: color,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            yAxisID: 'y',
        });

        // Dataset pour la limite de rate (horizontal line)
        const rateLimitData = new Array(labels.length).fill(provider.rate_limit);
        datasets.push({
            label: `${provider.provider_name} - Rate Limit`,
            type: 'line' as const,
            data: rateLimitData,
            borderColor: color.replace('1)', '0.5)'),
            backgroundColor: 'transparent',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            pointRadius: 0,
            pointHoverRadius: 0,
            yAxisID: 'y',
        });
    });

    return {
        labels,
        datasets,
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
            labels: {
                padding: 15,
                usePointStyle: true,
                filter: function (legendItem: any) {
                    // Ne montrer que les lignes principales (pas les rate limits)
                    return !legendItem.text.includes('Rate Limit');
                },
            },
        },
        title: {
            display: false,
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
            callbacks: {
                label: function (context: any) {
                    if (context.dataset.label.includes('Rate Limit')) {
                        return `Rate Limit: ${context.parsed.y}/min`;
                    }
                    return `${context.dataset.label}: ${context.parsed.y} emails`;
                },
            },
        },
    },
    scales: {
        x: {
            display: true,
            title: {
                display: true,
                text: 'Time (Next 60 minutes)',
            },
            grid: {
                display: false,
            },
        },
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            title: {
                display: true,
                text: 'Number of Emails',
            },
            beginAtZero: true,
            ticks: {
                stepSize: 1,
            },
            grid: {
                color: 'rgba(0, 0, 0, 0.1)',
            },
        },
    },
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
}));
</script>

<template>
    <div class="h-80">
        <div v-if="data.length > 0">
            <Line :data="chartData" :options="chartOptions" />
            <div class="mt-2 text-xs text-muted-foreground">
                <div class="flex flex-wrap gap-4">
                    <div v-for="(provider, index) in data" :key="provider.provider_name" class="flex items-center gap-1">
                        <div class="h-0.5 w-3 border-dashed" :style="{ borderColor: generateColor(index).replace('1)', '0.5)') }"></div>
                        <span>{{ provider.provider_name }} limit: {{ provider.rate_limit }}/min</span>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="flex h-full items-center justify-center text-muted-foreground">
            <div class="text-center">
                <div class="mb-2 text-lg font-medium">No scheduled emails</div>
                <div class="text-sm">No emails are scheduled for the next 60 minutes</div>
            </div>
        </div>
    </div>
</template>
