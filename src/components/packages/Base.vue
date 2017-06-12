<template>
    <main-layout>

        <section :class="{'package-actions': true, 'search-active': $route.name === 'packages-search'}">
            <button v-if="securityIssues" class="update alert" :disabled="hasChanges">{{ 'Security Report' | translate }}</button>
            <button v-else class="update" :disabled="hasChanges" @click="updatePackages">{{ 'ui.packages.updateButton' | translate }}</button>
            <button class="search" :disabled="hasChanges" @click="startSearch">{{ 'ui.packages.searchButton' | translate }}</button>
            <input ref="search" id="search" type="text" :placeholder="$t('ui.packages.searchPlaceholder')" autocomplete="off" v-model="searchInput" @keypress.esc.prevent="stopSearch" @keyup="search">
            <button class="cancel" @click="stopSearch">X</button>
        </section>

        <router-view ref="component" @changed="setHasChanges"></router-view>

        <div id="package-actions" :class="{ active: hasChanges }">
            <div class="inner">
                <p>{{ 'ui.packages.changesMessage' | translate }}</p>
                <button class="primary" @click="applyChanges">{{ 'ui.packages.changesApply' | translate }}</button>
                <button class="alert" @click="resetChanges">{{ 'ui.packages.changesReset' | translate }}</button>
            </div>
        </div>

    </main-layout>
</template>

<script>
    import routes from '../../router/routes';

    import MainLayout from '../layouts/Main';

    export default {
        components: { MainLayout },

        data: () => ({
            hasChanges: false,
            securityIssues: true,
            searchInput: '',
        }),

        methods: {
            updatePackages() {
                if (!confirm(this.$t('ui.packages.updateConfirm'))) {
                    return;
                }

                this.$store.dispatch(
                    'tasks/execute',
                    {
                        type: 'upgrade',
                    },
                ).then(
                    () => {
                        this.$emit('changed', false);
                        this.listPackages();
                    },
                );
            },

            startSearch() {
                this.$router.push(routes.packagesSearch);
                this.$nextTick(() => {
                    // run twice to await router rendering
                    this.$nextTick(() => {
                        this.$refs.search.focus();
                    });
                });
            },

            stopSearch() {
                this.searchInput = '';
                this.$router.push(routes.packages);
            },

            search() {
                if (this.$route.name === routes.packagesSearch.name) {
                    this.$router.push(
                        Object.assign(
                            { query: { q: this.searchInput } },
                            routes.packagesSearch,
                        ),
                    );
                }
            },

            setHasChanges(value) {
                this.hasChanges = value;
            },

            applyChanges() {
                this.$refs.component.applyChanges();
            },

            resetChanges() {
                this.$refs.component.resetChanges();
            },
        },

        watch: {
            $route(route) {
                this.hasChanges = false;

                if (route.name !== routes.packagesSearch.name) {
                    this.searchInput = '';
                }
            },
        },
    };
</script>
