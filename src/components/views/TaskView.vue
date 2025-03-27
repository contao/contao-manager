<template>
    <boxed-layout :wide="true" slotClass="view-task">
        <header class="view-task__header">
            <img src="../../assets/images/task.svg" width="80" height="80" alt="" class="view-task__icon">
            <h1 class="view-task__headline">{{ $t('ui.task.headline') }}</h1>
            <p class="view-task__description" v-if="taskStatus">{{ $t(`ui.task.${taskStatus}`) }}</p>

            <template v-if="isFailed">
                <p class="view-task__text">
                    {{ $t('ui.task.failedDescription1') }}<br>
                    {{ $t('ui.task.failedDescription2') }}
                </p>
                <p class="view-task__text"><br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ $t('ui.task.reportProblem') }}</a></p>

                <div class="view-task__actions">
                    <loading-button class="view-task__action" :loading="deletingTask" @click="deleteTask">{{ $t('ui.task.buttonClose') }}</loading-button>
                </div>
            </template>
            <template v-else-if="isPaused && allowContinue">
                <p class="view-task__text">
                    {{ $t('ui.task.pausedDescription') }}
                </p>

                <div class="view-task__actions">
                    <loading-button class="view-task__action" color="primary" :loading="deletingTask" @click="continueTask">{{ $t('ui.task.buttonContinue') }}</loading-button>
                    <loading-button class="view-task__action" :loading="deletingTask" @click="deleteTask">{{ $t('ui.task.buttonClose') }}</loading-button>
                </div>
            </template>
            <template v-else-if="hasTask">
                <div class="view-task__actions">
                    <loading-button class="view-task__action" color="alert" :loading="isAborting" @click="cancelTask" v-if="allowCancel && (isActive || isAborting)">{{ $t('ui.task.buttonCancel') }}</loading-button>

                    <loading-button class="view-task__action" color="primary" :loading="loadingMigrations" :disabled="(supportsMigrations && !hasDatabaseChanges) || deletingTask" @click="updateDatabase" v-if="requiresAudit">{{ $t('ui.task.buttonAudit') }}</loading-button>

                    <loading-button class="view-task__action" :loading="deletingTask" @click="deleteTask" v-if="!isActive && !isAborting">{{ $t('ui.task.buttonConfirm') }}</loading-button>
                    <check-box name="autoclose" :label="$t('ui.task.autoclose')" v-model="autoClose" v-if="isActive && allowAutoClose"/>
                </div>
            </template>
            <div class="view-task__loading" v-else>
                <loading-spinner/>
            </div>
        </header>

        <console-output
            class="view-task__main"
            :title="hasTask ? currentTask.title : $t('ui.task.loading')"
            :operations="currentTask.operations"
            :console-output="currentTask.console"
            v-if="hasTask"
        />

        <div class="view-task__sponsor" v-if="currentTask && currentTask.sponsor">
            <i18n-t keypath="ui.task.sponsor">
                <template #sponsor><br><a :href="currentTask.sponsor.link" target="_blank" rel="noreferrer noopener">{{ currentTask.sponsor.name }}</a></template>
            </i18n-t>
            <a href="https://to.contao.org/donate" target="_blank" rel="noreferrer noopener" class="view-task__donate"><img src="~contao-package-list/src/assets/images/funding.svg" alt="" width="20" height="20"></a>
        </div>
    </boxed-layout>
</template>

<script>
import { mapGetters, mapState } from 'vuex';
import task from '../../mixins/task';

import BoxedLayout from '../layouts/BoxedLayout';
import LoadingSpinner from 'contao-package-list/src/components/fragments/LoadingSpinner';
import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
import ConsoleOutput from '../fragments/ConsoleOutput';
import CheckBox from '../widgets/CheckBox';

export default {
        name: 'TaskView',
        mixins: [task],
        components: { BoxedLayout, LoadingSpinner, LoadingButton, ConsoleOutput, CheckBox },

        data: () => ({
            autoClose: false,
            favicons: null,
            faviconInterval: null,
        }),

        computed: {
            ...mapState('server/database', { supportsMigrations: 'supported', loadingMigrations: 'loading' }),
            ...mapGetters('server/database', { hasDatabaseChanges: 'hasChanges' }),
        },

        methods: {
            cancelTask() {
                if (confirm(this.$t('ui.task.confirmCancel'))) {
                    this.$store.dispatch('tasks/abort');
                }
            },

            continueTask() {
                this.$store.dispatch('tasks/continue');
            },

            async deleteTask() {
                await this.$store.dispatch('tasks/deleteCurrent');
            },

            async updateDatabase() {
                if (this.supportsMigrations) {
                    await this.$store.dispatch('tasks/deleteCurrent');
                    this.$store.commit('checkMigrations');
                } else {
                    window.open('/contao/install');
                }
            },

            updateFavicon() {
                let base;

                if (this.faviconInterval) {
                    clearInterval(this.faviconInterval);
                }

                const replaceIcon = (base) => {
                    this.favicons.forEach((el) => {
                        el.href = `${base}/${el.href.split('/').pop()}`;
                    });
                };

                switch (this.taskStatus) {
                    case 'active':
                        base = 'icons/task-active';
                        break;

                    case 'complete':
                        base = 'icons/task-success';
                        break;

                    case 'error':
                    case 'failed':
                    case 'stopped':
                        base = 'icons/task-error';
                        break;

                    default:
                        setTimeout(replaceIcon.bind(this, 'icons'), 2000);
                        return;
                }

                let replace = false;
                this.faviconInterval = setInterval(() => {
                    replace = !replace;
                    replaceIcon(replace ? base : 'icons');
                }, 2000);
            },
        },

        watch: {
            taskStatus() {
                this.updateFavicon();
            },

            autoClose(value) {
                window.localStorage.setItem('contao_manager_autoclose', value ? '1' : '0');
            },

            isComplete() {
                if (this.isComplete) {
                    this.$store.dispatch('server/database/get', false);
                }
            }
        },

        mounted() {
            this.favicons = document.querySelectorAll('link[class="favicon"]');
            this.updateFavicon();

            this.autoClose = window.localStorage.getItem('contao_manager_autoclose') === '1';

            this.$store.dispatch('server/database/get');
        },

        beforeUnmount() {
            this.updateFavicon();
        },
    }
</script>

<style lang="scss">
@use "~contao-package-list/src/assets/styles/defaults";

.view-task {
    &__header {
        margin-left: auto;
        margin-right: auto;
        padding: 40px 20px;
        text-align: center;
    }

    &__icon {
        background: var(--contao);
        border-radius: 10px;
        padding:10px;
    }

    &__headline {
        margin-top: 15px;
        font-size: 36px;
        font-weight: defaults.$font-weight-light;
        line-height: 1;
    }

    &__description {
        margin: 0;
        font-weight: defaults.$font-weight-bold;
    }

    &__actions {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin-top: 2em;

        @include defaults.screen(960) {
            flex-direction: row;
        }
    }

    .widget-button {
        width: 280px;
        height: 35px;
        margin: 5px;
        padding: 0 30px;
        line-height: 35px;

        @include defaults.screen(960) {
            width: auto;
        }
    }

    &__main {
        margin: 0 50px 50px;
    }

    &__loading {
        width: 30px;
        margin: 40px auto;

        .sk-circle {
            width: 30px;
            height: 30px;
        }
    }

    &__sponsor {
        margin: -30px 50px 50px;
        text-align: center;

        @include defaults.screen(960) {
            br {
                display: none;
            }
        }
    }

    &__donate {
        position: relative;
        top: 5px;
        margin-left: .5em;
        line-height: 0;
    }
}
</style>
