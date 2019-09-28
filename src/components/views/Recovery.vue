<template>
    <boxed-layout :wide="true" slotClass="view-recovery">
        <header class="view-recovery__header">
            <img src="../../assets/images/recovery.svg" width="80" height="80" alt="Contao Logo" class="view-recovery__icon">
            <h1 class="view-recovery__headline">{{ $t('ui.recovery.headline') }}</h1>
        </header>

        <main class="view-recovery__content">
            <p class="view-recovery__description">{{ $t('ui.recovery.description') }}</p>

            <div class="view-recovery__option">
                <h3>{{ $t('ui.recovery.repairHeadline') }}</h3>
                <p>{{ $t('ui.recovery.repairDescription') }}</p>
                <p><strong>{{ $t('ui.recovery.repairWarning') }}</strong></p>
                <p class="view-recovery__failed" v-if="repairFailed">{{ $t('ui.recovery.repairFailed') }}</p>
                <loading-button inline color="alert" icon="run" :disabled="repairFailed" :loading="repairStarted && !repairFailed" @click="runRepair">{{ $t('ui.recovery.repairButton') }}</loading-button>
            </div>

            <div class="view-recovery__option">
                <h3>{{ $t('ui.recovery.safeModeHeadline') }}</h3>
                <p>{{ $t('ui.recovery.safeModeDescription') }}</p>
                <button class="widget-button widget-button--inline widget-button--primary" :disabled="repairStarted && !repairFailed" @click="runSafeMode">{{ $t('ui.recovery.safeModeButton') }}</button>
            </div>
        </main>

    </boxed-layout>
</template>

<script>
    import { mapState } from 'vuex';

    import views from '../../router/views';

    import BoxedLayout from '../layouts/Boxed';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

    export default {
        components: { BoxedLayout, LoadingButton },

        data: () => ({
            repairStarted: false,
            repairFailed: false,
        }),

        computed: {
            ...mapState('tasks', { taskStatus: 'status' }),
        },

        methods: {
            async runRepair() {
                this.repairStarted = true;

                let task;
                const tasks = [
                    { name: 'contao/rebuild-cache' },
                    { name: 'composer/install' },
                    { name: 'composer/install', config: { 'remove-vendor': true } },
                ];

                while ((task = tasks.shift()) !== undefined) {
                    try {
                        await this.$store.dispatch('tasks/execute', task);
                        await this.$store.dispatch('tasks/deleteCurrent');
                        window.location.reload(true);
                        return;
                    } catch (err) {
                        if (this.taskStatus === 'failed') {
                            await this.$store.dispatch('tasks/deleteCurrent');
                            break;
                        }

                        await this.$store.dispatch('tasks/deleteCurrent');
                    }
                }

                this.repairFailed = true;
            },

            runSafeMode() {
                this.$store.commit('setSafeMode', true);
                this.$store.commit('setView', views.READY);
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .view-recovery {
        &__header {
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
            padding: 40px 0 10px;
            text-align: center;
        }

        &__icon {
            background: $contao-color;
            border-radius: 10px;
            padding:10px;
        }

        &__headline {
            margin-top: 15px;
            font-size: 36px;
            font-weight: $font-weight-light;
            line-height: 1;
        }

        &__content {
            margin: 0 30px 50px;

            @include screen(960) {
                margin-left: 50px;
                margin-right: 50px;
            }
        }

        &__description {
            font-weight: $font-weight-bold;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        &__option {
            margin: 50px 0 0;
            padding: 20px 20px 30px;
            background: #f5f9fa;
            text-align: center;

            h3 {
                position: relative;
                top: -40px;
                margin-bottom: -25px;
                font-size: 2em;
                font-weight: $font-weight-normal;
            }

            button {
                margin-top: 1.5em;
            }
        }

        &__failed {
            margin: 10px 0;
            color: $red-button;
            font-weight: $font-weight-bold;
        }
    }
</style>
