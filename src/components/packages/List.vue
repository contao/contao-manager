<template>
    <div class="package-list">
        <loader v-if="packages === null" class="package-list__status">
            <p>{{ 'ui.packagelist.loading' | translate }}</p>
        </loader>

        <package v-for="item in packages" :package="item" :key="item.name"/>
        <package v-for="item in $store.state.packages.add" :package="item" :key="item.name"/>
    </div>
</template>

<script>
    import Package from './Package';
    import Loader from '../fragments/Loader';

    export default {
        components: { Package, Loader },

        computed: {
            packages() {
                return this.$store.state.packages.installed;
            },
        },

        mounted() {
            this.$store.dispatch('packages/load');
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .package-list {
        position: relative;

        &__status {
            margin: 100px 0;
            text-align: center;
            font-size: 20px;
            line-height: 1.5em;

            .sk-circle {
                width: 100px;
                height: 100px;
                margin: 0 auto 40px;
            }
        }
    }
</style>
