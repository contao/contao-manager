<template>
    <div class="cloud-status" @mouseenter="open" @mouseleave="close" v-if="enabled">
        <loading-button :class="`cloud-status__button ${buttonClass}`" color="info" :icon="hasError ? 'cloud-off' : 'cloud'" :loading="isLoading" :disabled="hasError" @click="open">
            <template v-if="isReady">{{ $t('ui.cloudStatus.approx', { minutes: approxMinutes }) }}</template>
        </loading-button>

        <div ref="menu" class="cloud-status__popup" tabindex="-1" @focusout="close" @click="open" v-show="visible" v-if="isReady">
            <h2 class="cloud-status__headline">{{ $t('ui.cloudStatus.headline') }}</h2>
            <p class="cloud-status__version">{{ $t('ui.cloudStatus.version', { version: status.appVersion }) }}</p>
            <table>
                <tbody>
                    <tr>
                        <th>{{ $t('ui.cloudStatus.waitingTime') }}:</th>
                        <td>{{ waitingLabel }}</td>
                    </tr>
                    <tr>
                        <th>{{ $t('ui.cloudStatus.jobs') }}:</th>
                        <td>{{ status.numberOfJobsInQueue > 0 ? status.numberOfJobsInQueue + status.numberOfWorkers : `≤ ${status.numberOfWorkers}` }}</td>
                    </tr>
                    <tr>
                        <th>{{ $t('ui.cloudStatus.workers') }}:</th>
                        <td>{{ status.numberOfWorkers }}</td>
                    </tr>
                </tbody>
            </table>
            <a
                class="widget-button widget-button--info widget-button--link widget-button--small cloud-status__link"
                href="https://composer-resolver-cloud.statuspage.io/"
                target="_blank"
                rel="noreferrer noopener"
            >{{ $t('ui.cloudStatus.button') }}</a>
        </div>

        <div class="cloud-status__popup cloud-status__popup--error" tabindex="-1" v-else-if="hasError">
            <h2 class="cloud-status__headline cloud-status__headline--error">{{ $t('ui.cloudStatus.headline') }}</h2>
            <p class="cloud-status__error">{{ $t('ui.cloudStatus.error') }}</p>
            <a
                class="widget-button widget-button--info widget-button--link widget-button--small cloud-status__link"
                href="https://composer-resolver-cloud.statuspage.io/"
                target="_blank"
                rel="noreferrer noopener"
            >{{ $t('ui.cloudStatus.button') }}</a>
            <button class="widget-button widget-button--update widget-button--small" @click="refreshCloud" :title="$t('ui.cloudStatus.approxError')"></button>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';

export default {
    components: { LoadingButton },

    props: {
        buttonClass: String,
    },

    data: () => ({
        visible: false,

        timeout: null,
        mouseout: null,
    }),

    computed: {
        ...mapState('cloud', ['enabled', 'status']),
        ...mapGetters('cloud', ['isLoading', 'isReady', 'hasError']),

        waitingTime: (vm) => Math.round((vm.status.numberOfJobsInQueue * (vm.status.averageProcessingTimeInMs / 1000)) / Math.max(vm.status.numberOfWorkers, 1)),
        waitingMinutes: (vm) => Math.floor(vm.waitingTime / 60),
        waitingSeconds: (vm) => vm.waitingTime - 60 * vm.waitingMinutes,
        approxMinutes: (vm) => Math.round(vm.waitingTime / 60),

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
            this.visible = true;
            setTimeout(() => this.$refs.menu?.focus(), 0);
        },

        close(event) {
            if (event && this.$refs.menu?.contains(event.relatedTarget)) {
                return;
            }

            this.$refs.menu.blur();
            setTimeout(() => {
                this.visible = false;
            }, 100);
        },

        refreshCloud() {
            this.$store.commit('cloud/setStatus', null);
            this.fetchCloud();
        },

        async fetchCloud() {
            await this.$store.dispatch('cloud/fetch');

            if (this.enabled && !this.hasError) {
                this.timeout = setTimeout(this.fetchCloud, 1000 * 60);
            }
        },
    },

    mounted() {
        this.fetchCloud();
    },

    beforeUnmount() {
        clearTimeout(this.timeout);
    },
};
</script>

<style lang="scss">
.cloud-status {
    margin-left: 8px;
    position: relative;

    &__button {
        margin-left: 0;
        margin-right: 0;
        padding-left: 8px;
        cursor: help !important;
    }

    &__popup {
        position: absolute;
        text-align: center;
        left: 0;
        bottom: 54px;
        margin: 0;
        padding: 0 0 15px;
        outline: none;
        background: var(--form-bg);
        color: var(--text);
        border-bottom: 3px solid var(--contao);
        border-radius: 5px;
        box-shadow: 0 0 2px var(--shadow);
        z-index: 100;

        &:after {
            position: absolute;
            left: 38px;
            bottom: -6px;
            width: 0;
            height: 0;
            margin-left: -4px;
            border-style: solid;
            border-width: 4px 3.5px 0 3.5px;
            border-color: var(--contao) transparent transparent transparent;
            content: "";
        }

        &--error {
            color: var(--clr-btn);
            background-color: var(--btn-alert);
            border-color: var(--btn-alert);

            &:after {
                left: 27px;
                border-color: var(--btn-alert) transparent transparent transparent;
            }
        }
    }

    &__headline {
        padding: 15px 20px 0;
        font-size: 16px;
        white-space: pre;
    }

    &__version {
        margin: 0 0 8px;
        font-size: 12px;
    }

    &__link {
        display: inline-block;
        margin: 15px 10px 0;
    }

    &__error {
        padding: 8px 20px 8px;
        hyphens: auto;
    }

    table {
        width: 100%;
        margin-top: 12px;
        text-align: left;
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
        background: var(--table-odd-bg);
    }
}
</style>
