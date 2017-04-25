<template>
    <div class="error">
        <div>
            <h1><span>ERROR {{ error.status }}</span> {{ error.title }}</h1>
            <a :href="error.type" target="_blank" v-if="error.type !== 'about:blank'">More Information</a>
            <span>{{ error.detail }}</span>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        computed: mapState(['error']),
    };
</script>
