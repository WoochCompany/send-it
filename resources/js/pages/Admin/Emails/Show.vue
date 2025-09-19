<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, ArrowLeft, Calendar, Clock, Mail, Server, Tag, User } from 'lucide-vue-next';

interface MessageEvent {
    id: number;
    type: string;
    payload: string;
    created_at: string;
}

interface MessageTag {
    id: number;
    name: string;
}

interface Message {
    id: number;
    recipient: string;
    subject: string;
    body: string;
    status: string;
    scheduled_at: string | null;
    scheduled_requested_at: string;
    sent_at: string | null;
    created_at: string;
    updated_at: string;
    provider: {
        id: number;
        name: string;
        slug: string;
        provider: string;
    } | null;
    events: MessageEvent[];
    tags: MessageTag[];
}

interface Props {
    message: Message;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Emails',
        href: '/admin/emails',
    },
    {
        title: `Email #${props.message.id}`,
        href: `/admin/emails/${props.message.id}`,
    },
];

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'sent':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'scheduled':
            return 'outline';
        case 'failed':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const getEventTypeVariant = (type: string) => {
    switch (type) {
        case 'message_sent':
            return 'default';
        case 'message_rescheduled':
            return 'secondary';
        case 'send_failed':
            return 'destructive';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const formatEventPayload = (payload: string) => {
    try {
        const parsed = JSON.parse(payload);
        return JSON.stringify(parsed, null, 2);
    } catch {
        return payload;
    }
};

const formatDelay = (createdAt: string, scheduledAt: string) => {
    const created = new Date(createdAt);
    const scheduled = new Date(scheduledAt);
    const diffMs = scheduled.getTime() - created.getTime();

    // Si la date programmée est dans le passé par rapport à la création, retourner une chaîne vide
    if (diffMs <= 0) return '';

    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffDays > 0) {
        const remainingHours = diffHours % 24;
        if (remainingHours > 0) {
            return `(${diffDays}d ${remainingHours}h delay)`;
        }
        return `(${diffDays}d delay)`;
    } else if (diffHours > 0) {
        const remainingMinutes = diffMinutes % 60;
        if (remainingMinutes > 0) {
            return `(${diffHours}h ${remainingMinutes}m delay)`;
        }
        return `(${diffHours}h delay)`;
    } else if (diffMinutes > 0) {
        return `(${diffMinutes}m delay)`;
    }

    return '(< 1m delay)';
};
</script>

<template>
    <Head :title="`Email #${message.id} - ${message.recipient}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button as-child variant="outline" size="sm">
                        <Link href="/admin/emails">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Emails
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">Email #{{ message.id }}</h1>
                        <p class="text-sm text-muted-foreground">Email details and activity history</p>
                    </div>
                </div>
                <Badge :variant="getStatusVariant(message.status)" class="text-sm">
                    {{ message.status }}
                </Badge>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Message Details -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Mail class="h-5 w-5" />
                            Message Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Recipient</label>
                            <div class="mt-1 flex items-center gap-2">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <span class="font-mono">{{ message.recipient }}</span>
                            </div>
                        </div>

                        <Separator />

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Subject</label>
                            <p class="mt-1">{{ message.subject || 'No subject' }}</p>
                        </div>

                        <Separator />

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Tags</label>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <Badge v-for="tag in message.tags" :key="tag.id" variant="outline" class="text-xs">
                                    <Tag class="mr-1 h-3 w-3" />
                                    {{ tag.name }}
                                </Badge>
                            </div>
                            <div v-if="message.tags.length === 0" class="mt-1 text-sm text-muted-foreground">No tags assigned</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Timing & Provider -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock class="h-5 w-5" />
                            Timing & Provider
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created</label>
                            <div class="mt-1 flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span>{{ formatDate(message.created_at) }}</span>
                            </div>
                        </div>

                        <Separator />

                        <div v-if="message.scheduled_requested_at" class="flex items-center gap-2">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-muted-foreground">Requested for</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <span>
                                        {{ formatDate(message.scheduled_requested_at) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1" v-if="message.scheduled_at">
                                <label class="text-sm font-medium text-muted-foreground">Scheduled</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <span>
                                        {{ formatDate(message.scheduled_at) }}
                                        <span class="ml-1 text-muted-foreground">
                                            {{ formatDelay(message.scheduled_requested_at, message.scheduled_at) }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="message.sent_at">
                            <label class="text-sm font-medium text-muted-foreground">Sent</label>
                            <div class="mt-1 flex items-center gap-2">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                <span>{{ formatDate(message.sent_at) }}</span>
                            </div>
                        </div>

                        <Separator />

                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Provider</label>
                            <div v-if="message.provider" class="mt-1 flex items-center gap-2">
                                <Server class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="font-medium">{{ message.provider.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ message.provider.provider }} • {{ message.provider.slug }}</p>
                                </div>
                            </div>
                            <p v-else class="mt-1 text-sm text-muted-foreground">No provider assigned</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Events History -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5" />
                        Event History
                        <Badge variant="secondary">{{ message.events.length }} events</Badge>
                    </CardTitle>
                    <CardDescription> Complete timeline of all events related to this message </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="message.events.length > 0" class="space-y-4">
                        <div v-for="event in message.events" :key="event.id" class="flex items-start gap-3 rounded-lg border p-3">
                            <Badge :variant="getEventTypeVariant(event.type)" class="mt-0.5 text-xs">
                                {{ event.type }}
                            </Badge>
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-center gap-2">
                                    <span class="text-sm font-medium">{{
                                        event.type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase())
                                    }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ formatDate(event.created_at) }}
                                    </span>
                                </div>
                                <div v-if="event.payload" class="mt-2">
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-muted-foreground hover:text-foreground">View payload</summary>
                                        <pre class="mt-2 overflow-x-auto rounded bg-muted p-2 text-xs">{{ formatEventPayload(event.payload) }}</pre>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-muted-foreground">
                        <Activity class="mx-auto mb-2 h-8 w-8 opacity-50" />
                        <p>No events recorded for this message</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
