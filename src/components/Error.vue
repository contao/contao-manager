<template>
    <div class="error">
        <div>
            <h1>
                <span>ERROR<a :href="'https://httpstatuses.com/'+error.status" target="_blank" v-if="error.status"> {{ error.status }}</a></span>
                {{ error.title }}
            </h1>
            <a :href="error.type" target="_blank" v-if="error.type !== 'about:blank'">{{ 'ui.error.moreLink' | translate }}</a>
            <div class="details" v-if="error.detail">{{ error.detail }}</div>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        computed: mapState(['error']),
    };
</script>
