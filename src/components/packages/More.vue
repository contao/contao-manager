<template>
    <div class="more">
        <button @click="open">{{ 'ui.package.more' | translate }}</button>
        <ul v-if="visible" v-once>
            <li v-if="homepage"><a :href="homepage" target="_blank">{{ 'ui.package.homepage' | translate }}</a></li>
            <li v-if="name"><a :href="'https://packagist.org/packages/'+name" target="_blank" @touchstart="ignore">{{ 'ui.package.packagist' | translate }}</a></li>
            <li v-if="support && support.docs"><a :href="support.docs" target="_blank" @touchstart="ignore">{{ 'ui.package.support_docs' | translate }}</a></li>
            <li v-if="support && support.wiki"><a :href="support.wiki" target="_blank" @touchstart="ignore">{{ 'ui.package.support_wiki' | translate }}</a></li>
            <li v-if="support && support.forum"><a :href="support.forum" target="_blank" @touchstart="ignore">{{ 'ui.package.support_forum' | translate }}</a></li>
            <li v-if="support && support.issues"><a :href="support.issues" target="_blank" @touchstart="ignore">{{ 'ui.package.support_issues' | translate }}</a></li>
            <li v-if="support && support.source"><a :href="support.source" target="_blank" @touchstart="ignore">{{ 'ui.package.support_source' | translate }}</a></li>
            <li v-if="support && support.irc"><a :href="support.irc" target="_blank" @touchstart="ignore">{{ 'ui.package.support_irc' | translate }}</a></li>
            <li v-if="support && support.email"><a :href="'mailto:'+support.email" target="_blank" @touchstart="ignore">{{ 'ui.package.support_email' | translate }}</a></li>
            <li v-if="support && support.rss"><a :href="support.rss" target="_blank" @touchstart="ignore">{{ 'ui.package.support_rss' | translate }}</a></li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: ['name', 'homepage', 'support'],

        data: () => ({
            visible: false,
        }),

        methods: {
            open(e) {
                e.stopPropagation();
                this.visible = !this.visible;
            },

            close() {
                this.visible = false;
            },

            ignore(e) {
                e.stopPropagation();
            },
        },

        mounted() {
            window.addEventListener('click', this.close);
            window.addEventListener('touchstart', this.close);
        },
    };
</script>
