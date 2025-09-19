<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Mail, Server, X } from 'lucide-vue-next';

interface Message {
    id: number;
    recipient: string;
    subject: string;
    status: string;
    created_at: string;
    sent_at: string | null;
}

interface Provider {
    id: number;
    slug: string;
    name: string;
    provider: string;
    config: string;
    is_default: boolean;
    messages_per_minute: number;
    messages_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    provider: Provider;
    recentMessages: Message[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Providers',
        href: '/admin/providers',
    },
    {
        title: props.provider.name,
        href: `/admin/providers/${props.provider.id}`,
    },
];

const getProviderBadgeVariant = (provider: string) => {
    switch (provider.toLowerCase()) {
        case 'smtp':
            return 'default';
        case 'mailgun':
            return 'secondary';
        case 'sendgrid':
            return 'outline';
        case 'ses':
            return 'destructive';
        default:
            return 'default';
    }
};

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

const parseConfig = (configString: string) => {
    try {
        return JSON.parse(configString);
    } catch {
        return {};
    }
};

const config = parseConfig(props.provider.config);
</script>

<template>
    <Head :title="`Provider: ${provider.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/admin/providers">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Providers
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">{{ provider.name }}</h1>
                        <p class="text-sm text-muted-foreground">Provider details and configuration</p>
                    </div>
                </div>
                <Badge :variant="getProviderBadgeVariant(provider.provider)" class="text-sm">
                    {{ provider.provider.toUpperCase() }}
                </Badge>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Provider Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Server class="h-5 w-5" />
                            Provider Information
                        </CardTitle>
                        <CardDescription>Basic configuration and status</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Name</label>
                                <p class="text-sm font-medium">{{ provider.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Slug</label>
                                <code class="rounded bg-muted px-1.5 py-0.5 text-sm">{{ provider.slug }}</code>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Type</label>
                                <Badge :variant="getProviderBadgeVariant(provider.provider)">
                                    {{ provider.provider.toUpperCase() }}
                                </Badge>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Default Provider</label>
                                <div class="flex items-center gap-2">
                                    <Check v-if="provider.is_default" class="h-4 w-4 text-green-600" />
                                    <X v-else class="h-4 w-4 text-gray-400" />
                                    <span class="text-sm">{{ provider.is_default ? 'Yes' : 'No' }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Rate Limit</label>
                                <p class="text-sm font-medium">{{ provider.messages_per_minute }} messages/minute</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Total Messages</label>
                                <Badge variant="secondary">{{ provider.messages_count }}</Badge>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created</label>
                            <p class="text-sm">{{ formatDate(provider.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Configuration -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Server class="h-5 w-5" />
                            Configuration
                        </CardTitle>
                        <CardDescription>Provider connection settings</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="(value, key) in config" :key="key" class="flex items-center justify-between">
                                <label class="text-sm font-medium text-muted-foreground capitalize">
                                    {{ key.replace('_', ' ') }}
                                </label>
                                <div class="font-mono text-sm">
                                    <span v-if="key.toLowerCase().includes('password') || key.toLowerCase().includes('secret')"> •••••••• </span>
                                    <span v-else>{{ value }}</span>
                                </div>
                            </div>
                            <div v-if="Object.keys(config).length === 0" class="text-sm text-muted-foreground">No configuration available</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Messages -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mail class="h-5 w-5" />
                        Recent Messages
                        <Badge variant="secondary">{{ recentMessages.length }}</Badge>
                    </CardTitle>
                    <CardDescription>Latest messages sent through this provider</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="recentMessages.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Recipient</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Sent At</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="message in recentMessages" :key="message.id">
                                    <TableCell>
                                        <div class="font-medium">{{ message.recipient }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="max-w-[200px] truncate">
                                            {{ message.subject || 'No subject' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusVariant(message.status)"
                                        >
                                            {{ message.status }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="message.sent_at" class="text-sm">
                                            {{ formatDate(message.sent_at) }}
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">Not sent</span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button asChild variant="ghost" size="sm">
                                            <Link :href="`/admin/emails/${message.id}`"> View </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div v-else class="p-8 text-center">
                        <Mail class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                        <h3 class="mb-2 text-lg font-medium text-muted-foreground">No messages yet</h3>
                        <p class="text-sm text-muted-foreground">No messages have been sent through this provider yet.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
