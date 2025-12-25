<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { resolveUrl } from '@/utils/url';

interface Props {
    href: string;
    as?: string;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    data?: Record<string, unknown>;
    replace?: boolean;
    preserveScroll?: boolean;
    preserveState?: boolean;
    only?: string[];
    except?: string[];
    headers?: Record<string, string>;
    queryStringArrayFormat?: 'brackets' | 'indices';
}

const props = withDefaults(defineProps<Props>(), {
    method: 'get',
});

const resolvedHref = computed(() => resolveUrl(props.href));
</script>

<template>
    <Link
        :href="resolvedHref"
        :as="as"
        :method="method"
        :data="data"
        :replace="replace"
        :preserve-scroll="preserveScroll"
        :preserve-state="preserveState"
        :only="only"
        :except="except"
        :headers="headers"
        :query-string-array-format="queryStringArrayFormat"
    >
        <slot />
    </Link>
</template>
