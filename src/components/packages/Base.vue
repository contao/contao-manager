<template>
    <main-layout>

        <section :class="{'package-actions': true, 'search-active': $route.name === 'packages-search'}">
            <button class="update" @click="updatePackages">Update Packages</button>
            <button class="search" :disabled="hasChanges" @click="startSearch">Search packages</button>
            <input ref="search" id="search" type="text" placeholder="Search Packages…" autocomplete="off" v-model="searchInput" @keypress.esc.prevent="stopSearch" @keyup="search">
            <button class="cancel" @click="stopSearch">X</button>
        </section>

        <router-view ref="component" @changed="setHasChanges"></router-view>

        <div id="package-actions" :class="{ active: hasChanges }">
            <div class="inner">
                <p>You have unconfirmed changes.</p>
                <button class="primary" @click="applyChanges">Apply changes</button>
                <button class="alert" @click="resetChanges">Reset changes</button>
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
            searchInput: '',
        }),
        methods: {
            updatePackages() {
                if (!confirm('All packages will be updated to their latest version. Do you want to continue?')) {
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
