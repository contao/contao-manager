<template>
    <div class="cloud-status" v-if="enabled">
        <loading-button :class="`cloud-status__button ${buttonClass}`" color="info" icon="cloud" :loading="loading" @mouseover="open" @mouseout="close" @click="open">
            {{ $t('ui.cloudStatus.approx', { minutes: approxMinutes }) }}
        </loading-button>
        <div class="cloud-status__popup" tabindex="-1" @blur="close" @mouseover="open" @mouseout="close" @click="open" v-show="visible">
            <h2 class="cloud-status__headline">{{ $t('ui.cloudStatus.headline') }}</h2>
            <p class="cloud-status__version">{{ $t('ui.cloudStatus.version', { version: cloudStatus.appVersion }) }}</p>
            <count-down ref="countdown" :seconds="60" :loading="loading"/>
            <table>
                <tr>
                    <th>{{ $t('ui.cloudStatus.waitingTime') }}:</th>
                    <td>{{ waitingLabel }}</td>
                </tr>
                <tr>
                    <th>{{ $t('ui.cloudStatus.jobs') }}:</th>
                    <td>{{ cloudStatus.numberOfJobsInQueue > 0 ? (cloudStatus.numberOfJobsInQueue + cloudStatus.numberOfWorkers) : `≤ ${cloudStatus.numberOfWorkers}` }}</td>
                </tr>
                <tr>
                    <th>{{ $t('ui.cloudStatus.workers') }}:</th>
                    <td>{{ cloudStatus.numberOfWorkers }}</td>
                </tr>
            </table>
        </div>
    </div>
</template>

<script>
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import CountDown from './CountDown';

    export default {
        components: { LoadingButton ,CountDown },

        props: {
            buttonClass: String,
        },

        data: () => ({
            enabled: false,
            loading: true,
            visible: false,

            cloudStatus: {
                appVersion: 0,
                numberOfJobsInQueue: 0,
                averageProcessingTimeInMs: 0,
                numberOfWorkers: 0,
            },

            timeout: null,
            mouseout: null,
        }),

        computed: {
            waitingTime: vm => Math.round(vm.cloudStatus.numberOfJobsInQueue * (vm.cloudStatus.averageProcessingTimeInMs / 1000) / Math.max(vm.cloudStatus.numberOfWorkers, 1)),
            waitingMinutes: vm => Math.floor(vm.waitingTime / 60),
            waitingSeconds: vm => vm.waitingTime - (60 * vm.waitingMinutes),
            approxMinutes: vm => Math.round(vm.waitingTime / 60),

            waitingLabel() {
                if (!this.waitingTime) {
                    return this.$t('ui.cloudStatus.none');
                }

                if (!this.waitingSeconds) {
                    return this.$t('ui.cloudStatus.short', { minutes: this.waitingMinutes });
                }

                return this.$t('ui.cloudStatus.long', { minutes: this.waitingMinutes, seconds: this.waitingSeconds });
            },
        },

        methods: {
            open() {
                clearTimeout(this.mouseout);
                this.visible = true;
            },

            close() {
                this.mouseout = setTimeout(() => {
                    this.visible = false;
                }, 300);
            },

            async fetchCloud() {
                try {
                    this.cloudStatus = (await this.$http.get(
                        'https://www.composer-resolver.cloud/',
                        { responseType: 'json', headers: { 'Composer-Resolver-Client': 'contao' } },
                    )).body;
                    this.enabled = true;
                } catch (err) {
                    this.enabled = false;
                    throw err;
                }

                this.loading = false;
                this.timeout = setTimeout(this.fetchCloud, 1000 * 60);

                if (this.$refs.countdown) {
                    this.$refs.countdown.start();
                }
            },
        },

        async mounted() {
            try {
                const config = await this.$store.dispatch('server/config/get');

                if (!config.cloud || !config.cloud.enabled) {
                    return;
                }
            } catch (err) {
                return;
            }

            this.enabled = true;
            this.fetchCloud();
        },

        beforeDestroy() {
            clearTimeout(this.timeout);
        }
    };
</script>

<style lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .cloud-status {
        display: none;
        position: relative;

        @include screen(600) {
            display: block;
        }

        &__button {
            cursor: help !important;
        }

        &__popup {
            position: absolute;
            text-align: left;
            left: 8px;
            bottom: 55px;
            margin: 0;
            padding: 0;
            outline: none;
            background: #fff;
            color: $text-color;
            border-bottom: 3px solid $contao-color;
            box-shadow: $shadow-color 0 -1px 2px;
            z-index: 100;

            &:after {
                position: absolute;
                left: 38px;
                bottom: -7px;
                width: 0;
                height: 0;
                margin-left: -4px;
                border-style: solid;
                border-width: 4px 3.5px 0 3.5px;
                border-color: $contao-color transparent transparent transparent;
                content: "";
            }
        }

        &__headline {
            padding: 15px 20px 0;
            font-size: 16px;
            white-space: pre;
        }

        &__version {
            text-align: center;
            margin: 0 0 8px;
            font-size: 12px;
        }

        table {
            width: 100%;
            margin-top: 12px;
            margin-bottom: 15px;
            border-spacing: 0;
            border-collapse: collapse;
        }

        th {
            padding: 3px 5px 3px 20px;
        }

        td {
            padding: 3px 20px 3px 0;
        }

        tr:nth-child(odd) {
            background: #f0f0f0;
        }
    }
</style>
