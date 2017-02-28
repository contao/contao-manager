<template>
    <main-layout>

        <section :class="{'package-actions': true, 'search-active': $route.name === 'packages-search'}">
            <button class="update" disabled style="text-decoration: line-through">Check for Updates</button>
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
    import MainLayout from '../layouts/Main';

    export default {
        components: { MainLayout },
        data: () => ({
            hasChanges: false,
            searchInput: '',
        }),
        methods: {
            startSearch() {
                this.$router.push({ name: 'packages-search' });
                this.$nextTick(() => {
                    // run twice to await router rendering
                    this.$nextTick(() => {
                        this.$refs.search.focus();
                    });
                });
            },
            stopSearch() {
                this.searchInput = '';
                this.$router.push({ name: 'packages' });
            },
            search() {
                if (this.$route.name === 'packages-search') {
                    this.$router.push({ name: 'packages-search', query: { q: this.searchInput } });
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

                if (route.name !== 'packages-search') {
                    this.searchInput = '';
                }
            },
        },
    };
</script>
