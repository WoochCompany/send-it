<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Mail, Search, Tag, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Tag {
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
    sent_at: string | null;
    created_at: string;
    tags: Tag[];
    provider: {
        id: number;
        name: string;
        slug: string;
    } | null;
    events: Array<{
        id: number;
        type: string;
        created_at: string;
    }>;
}

interface Props {
    messages: {
        data: Message[];
        links: any[];
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        status?: string;
        search?: string;
        tags?: number[];
    };
    statusOptions: Array<{
        value: string;
        label: string;
    }>;
    availableTags: Tag[];
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
];

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const selectedTags = ref<number[]>(props.filters.tags || []);

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

const selectedTagNames = computed(() => {
    return selectedTags.value
        .map((tagId) => {
            const tag = props.availableTags.find((t) => t.id === tagId);
            return tag ? tag.name : '';
        })
        .filter(Boolean);
});

const addTag = (tagId: string) => {
    const id = parseInt(tagId);
    if (!selectedTags.value.includes(id)) {
        selectedTags.value.push(id);
    }
};

const removeTag = (tagId: number) => {
    selectedTags.value = selectedTags.value.filter((id) => id !== tagId);
};

const applyFilters = () => {
    router.get(
        '/admin/emails',
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
            tags: selectedTags.value.length > 0 ? selectedTags.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    selectedTags.value = [];
    router.get(
        '/admin/emails',
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch(
    [searchQuery, statusFilter, selectedTags],
    () => {
        const timeoutId = setTimeout(applyFilters, 300);
        return () => clearTimeout(timeoutId);
    },
    { deep: true },
);
</script>

<template>
    <Head title="Email Administration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Email Administration</h1>
                    <p class="text-sm text-muted-foreground">Monitor and track all email messages sent through the system</p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="h-5 w-5" />
                        Filters
                    </CardTitle>
                    <CardDescription>Filter emails by status or search for specific messages</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-end gap-4">
                            <div class="flex-1">
                                <label class="mb-2 block text-sm font-medium">Search</label>
                                <div class="relative">
                                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input v-model="searchQuery" placeholder="Search by recipient or subject..." class="pl-10" />
                                </div>
                            </div>
                            <div class="w-48">
                                <label class="mb-2 block text-sm font-medium">Status</label>
                                <Select v-model="statusFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="w-48">
                                <label class="mb-2 block text-sm font-medium">Add Tags</label>
                                <Select @update:modelValue="addTag">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select tags" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="tag in props.availableTags.filter((t) => !selectedTags.includes(t.id))"
                                            :key="tag.id"
                                            :value="tag.id.toString()"
                                        >
                                            {{ tag.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button @click="clearFilters" variant="outline"> Clear Filters </Button>
                        </div>

                        <!-- Selected Tags -->
                        <div v-if="selectedTags.length > 0" class="flex flex-wrap gap-2">
                            <Badge v-for="tagId in selectedTags" :key="tagId" variant="secondary" class="flex items-center gap-1">
                                <Tag class="h-3 w-3" />
                                {{ props.availableTags.find((t) => t.id === tagId)?.name }}
                                <X class="h-3 w-3 cursor-pointer hover:text-destructive" @click="removeTag(tagId)" />
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Messages Table -->
            <Card class="flex-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Mail class="h-5 w-5" />
                        Messages
                        <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
                            {{ messages.total }} total
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Recipient</TableHead>
                                    <TableHead>Subject</TableHead>
                                    <TableHead>Tags</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Provider</TableHead>
                                    <TableHead>Scheduled</TableHead>
                                    <TableHead>Sent</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="message in messages.data" :key="message.id">
                                    <TableCell>
                                        <div class="font-medium">
                                            {{ message.recipient }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="max-w-[200px] truncate">
                                            {{ message.subject || 'No subject' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="tag in message.tags" :key="tag.id" variant="outline" class="text-xs">
                                                {{ tag.name }}
                                            </Badge>
                                            <span v-if="message.tags.length === 0" class="text-xs text-muted-foreground"> No tags </span>
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
                                        <span v-if="message.provider" class="text-sm">
                                            {{ message.provider.name }}
                                        </span>
                                        <span v-else class="text-sm text-muted-foreground"> No provider </span>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="message.scheduled_at" class="text-sm">
                                            {{ formatDate(message.scheduled_at) }}
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">Not scheduled</span>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="message.sent_at" class="text-sm">
                                            {{ formatDate(message.sent_at) }}
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">Not sent</span>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(message.created_at) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button asChild variant="ghost" size="sm">
                                            <Link :href="`/admin/emails/${message.id}`">
                                                <Eye class="mr-2 h-4 w-4" />
                                                View
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between border-t p-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ messages.from || 0 }} to {{ messages?.to || 0 }} of {{ messages.total }} results
                        </div>
                        <div class="flex gap-2">
                            <template v-for="link in messages.links" :key="link.label">
                                <Button
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url"
                                    @click="router.visit(link.url)"
                                    size="sm"
                                >
                                    <span v-html="link.label" />
                                </Button>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
