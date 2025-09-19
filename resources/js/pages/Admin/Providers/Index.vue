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
import { Check, Eye, Search, Server, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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
    providers: {
        data: Provider[];
        links: any[];
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        type?: string;
    };
    typeOptions: Array<{
        value: string;
        label: string;
    }>;
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
];

const searchQuery = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || 'all');

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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const applyFilters = () => {
    router.get(
        '/admin/providers',
        {
            search: searchQuery.value || undefined,
            type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    typeFilter.value = 'all';
    router.get(
        '/admin/providers',
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch([searchQuery, typeFilter], () => {
    const timeoutId = setTimeout(applyFilters, 300);
    return () => clearTimeout(timeoutId);
});
</script>

<template>
    <Head title="Provider Administration" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Provider Administration</h1>
                    <p class="text-sm text-muted-foreground">Manage and monitor all message providers configured in the system</p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="h-5 w-5" />
                        Filters
                    </CardTitle>
                    <CardDescription>Filter providers by type or search for specific providers</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            <label class="mb-2 block text-sm font-medium">Search</label>
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="searchQuery" placeholder="Search by name, slug, or provider type..." class="pl-10" />
                            </div>
                        </div>
                        <div class="w-48">
                            <label class="mb-2 block text-sm font-medium">Type</label>
                            <Select v-model="typeFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button @click="clearFilters" variant="outline"> Clear Filters </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Providers Table -->
            <Card class="flex-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Server class="h-5 w-5" />
                        Providers
                        <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
                            {{ providers.total }} total
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Default</TableHead>
                                    <TableHead>Rate Limit</TableHead>
                                    <TableHead>Messages</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="provider in providers.data" :key="provider.id">
                                    <TableCell>
                                        <div class="font-medium">
                                            {{ provider.name }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getProviderBadgeVariant(provider.provider)">
                                            {{ provider.provider.toUpperCase() }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <code class="rounded bg-muted px-1.5 py-0.5 text-sm">
                                            {{ provider.slug }}
                                        </code>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center">
                                            <Check v-if="provider.is_default" class="h-4 w-4 text-green-600" />
                                            <X v-else class="h-4 w-4 text-gray-400" />
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm"> {{ provider.messages_per_minute }}/min </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {{ provider.messages_count }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(provider.created_at) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button asChild variant="ghost" size="sm">
                                            <Link :href="`/admin/providers/${provider.id}`">
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
                            Showing {{ providers.from || 0 }} to {{ providers.to || 0 }} of {{ providers.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in providers.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                @click="router.visit(link.url)"
                                size="sm"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
