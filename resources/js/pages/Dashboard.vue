<script setup lang="ts">
import BarChart from '@/components/charts/BarChart.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import LineChart from '@/components/charts/LineChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, CheckCircle, Clock, Eye, Mail, Server, TrendingUp } from 'lucide-vue-next';

interface Stats {
    emails_today: number;
    emails_this_week: number;
    emails_this_month: number;
    total_emails: number;
    pending_emails: number;
    scheduled_emails: number;
    total_providers: number;
    default_provider: string;
    success_rate: number;
}

interface ChartData {
    scheduled_by_provider: Array<{
        provider_name: string;
        rate_limit: number;
        time_slots: Array<{ time: string; count: number; timestamp: string }>;
    }>;
    status_distribution: Array<{ status: string; count: number }>;
    daily_messages: Array<{ date: string; label: string; count: number }>;
    provider_usage: Array<{ provider_name: string; count: number }>;
}

interface Message {
    id: number;
    recipient: string;
    subject: string;
    status: string;
    created_at: string;
    provider?: { name: string };
}

interface Props {
    stats: Stats;
    charts: ChartData;
    recent_messages: Message[];
    recent_errors: Message[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'sent':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'scheduled':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                    <p class="text-sm text-muted-foreground">Email system overview and statistics</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today</CardTitle>
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.emails_today }}</div>
                        <p class="text-xs text-muted-foreground">emails sent today</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">This Week</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.emails_this_week }}</div>
                        <p class="text-xs text-muted-foreground">emails this week</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Success Rate</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.success_rate }}%</div>
                        <p class="text-xs text-muted-foreground">delivery success rate</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending_emails }}</div>
                        <p class="text-xs text-muted-foreground">emails pending</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Row -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Scheduled Emails by Provider (Next 60 minutes) -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Server class="h-5 w-5" />
                            Scheduled (Next 60min)
                        </CardTitle>
                        <CardDescription> Emails scheduled by provider in the next hour </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarChart :data="charts.scheduled_by_provider" />
                    </CardContent>
                </Card>

                <!-- Status Distribution -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CheckCircle class="h-5 w-5" />
                            Status Distribution
                        </CardTitle>
                        <CardDescription> Breakdown of email statuses </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DonutChart :data="charts.status_distribution" />
                    </CardContent>
                </Card>

                <!-- Daily Messages Trend -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            7-Day Trend
                        </CardTitle>
                        <CardDescription> Messages sent over the last 7 days </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <LineChart :data="charts.daily_messages" />
                    </CardContent>
                </Card>
            </div>

            <!-- Additional Stats and Recent Activity -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Recent Messages -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Mail class="h-5 w-5" />
                            Recent Messages
                        </CardTitle>
                        <CardDescription>Latest emails sent through the system</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="recent_messages.length > 0" class="divide-y">
                            <div v-for="message in recent_messages" :key="message.id" class="flex items-center justify-between p-4">
                                <div class="space-y-1">
                                    <p class="text-sm leading-none font-medium">
                                        {{ message.recipient }}
                                    </p>
                                    <p class="max-w-[200px] truncate text-sm text-muted-foreground">
                                        {{ message.subject || 'No subject' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :class="getStatusVariant(message.status)" variant="secondary">
                                        {{ message.status }}
                                    </Badge>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link :href="`/admin/emails/${message.id}`">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center">
                            <Mail class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <p class="text-sm text-muted-foreground">No recent messages</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Errors -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5" />
                            Recent Errors
                        </CardTitle>
                        <CardDescription>Failed emails that need attention</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="recent_errors.length > 0" class="divide-y">
                            <div v-for="error in recent_errors" :key="error.id" class="flex items-center justify-between p-4">
                                <div class="space-y-1">
                                    <p class="text-sm leading-none font-medium">
                                        {{ error.recipient }}
                                    </p>
                                    <p class="max-w-[200px] truncate text-sm text-muted-foreground">
                                        {{ error.subject || 'No subject' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(error.created_at) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="destructive">Failed</Badge>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link :href="`/admin/emails/${error.id}`">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center">
                            <CheckCircle class="mx-auto mb-4 h-12 w-12 text-green-500" />
                            <p class="text-sm text-muted-foreground">No recent errors!</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- System Overview -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Total Messages</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_emails }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Active Providers</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_providers }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Default Provider</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-lg font-medium">{{ stats.default_provider }}</div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
