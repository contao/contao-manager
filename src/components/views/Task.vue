<template>
    <boxed-layout :wide="true" slotClass="view-task">
        <header class="view-task__header">
            <img src="../../assets/images/task.svg" width="80" height="80" alt="" class="view-task__icon">
            <h1 class="view-task__headline">{{ $t('ui.task.headline') }}</h1>
            <!--<p class="view-task__description">{{ $t('ui.task.description') }}</p>-->
            <p class="view-task__description" v-if="taskStatus">{{ $t(`ui.task.${taskStatus}`) }}</p>

            <template class="view-task__summary" v-if="isFailed && !awaitTask">
                <p class="view-task__text">
                    {{ $t('ui.task.failedDescription1') }}<br>
                    {{ $t('ui.task.failedDescription2') }}
                </p>
                <p class="view-task__text"><br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ $t('ui.task.reportProblem') }}</a></p>

                <div class="view-task__actions">
                    <loading-button class="view-task__action" :loading="deletingTask" @click="deleteTask">{{ $t('ui.task.buttonClose') }}</loading-button>
                </div>
            </template>
            <template v-else-if="hasTask && !awaitTask">
                <div class="view-task__actions">
                    <loading-button class="view-task__action" :loading="isAborting" @click="cancelTask" v-if="allowCancel && (isActive || isAborting)">{{ $t('ui.task.buttonCancel') }}</loading-button>

                    <loading-button class="view-task__action" color="primary" :loading="loadingMigrations" :disabled="(supportsMigrations && totalChanges === 0) || deletingTask" @click="updateDatabase" v-if="requiresAudit">{{ $t('ui.task.buttonAudit') }}</loading-button>

                    <loading-button class="view-task__action" :loading="deletingTask" @click="deleteTask" v-if="!isActive && !isAborting">{{ $t('ui.task.buttonConfirm') }}</loading-button>
                    <checkbox name="autoclose" :label="$t('ui.task.autoclose')" v-model="autoClose" v-if="isActive && allowAutoClose"/>
                </div>
            </template>
            <div class="view-task__loading" v-else>
                <loader/>
            </div>
        </header>

        <console
            class="view-task__main"
            :title="hasTask ? currentTask.title : $t('ui.task.loading')"
            :operations="currentTask.operations"
            :console-output="currentTask.console"
            :show-console.sync="showConsole"
            v-if="hasTask"
        />
    </boxed-layout>
</template>

<script>
import { mapGetters, mapState } from 'vuex';
    import task from '../../mixins/task';
    import routes from '../../router/routes';

    import BoxedLayout from '../layouts/Boxed';
    import Loader from 'contao-package-list/src/components/fragments/Loader';
    import LoadingButton from 'contao-package-list/src/components/fragments/LoadingButton';
    import Console from '../fragments/Console';
    import Checkbox from '../widgets/Checkbox';

    export default {
        name: 'TaskView',
        mixins: [task],
        components: { BoxedLayout, Loader, LoadingButton, Console, Checkbox },

        data: () => ({
            showConsole: false,
            autoClose: false,
            favicons: null,
            faviconInterval: null,
        }),

        computed: {
            ...mapState('server/database', { supportsMigrations: 'supported', loadingMigrations: 'loading' }),
            ...mapGetters('server/database', ['totalChanges']),
        },

        methods: {
            cancelTask() {
                if (confirm(this.$t('ui.task.confirmCancel'))) {
                    this.$store.dispatch('tasks/abort');
                }
            },

            async deleteTask() {
                const reload = this.isError;

                await this.$store.dispatch('tasks/deleteCurrent')

                if (reload) {
                    window.location.reload();
                }
            },

            async updateDatabase() {
                if (this.supportsMigrations) {
                    await this.$store.dispatch('tasks/deleteCurrent');
                    this.$router.push({ name: routes.databaseMigration.name });
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

            showConsole(value) {
                window.localStorage.setItem('contao_manager_console', value ? '1' : '0');
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

            this.showConsole = window.localStorage.getItem('contao_manager_console') === '1';
            this.autoClose = window.localStorage.getItem('contao_manager_autoclose') === '1';

            this.$store.dispatch('server/database/get');
        },

        beforeDestroy() {
            this.updateFavicon();
        },
    }
</script>

<style lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .view-task {
        &__header {
            margin-left: auto;
            margin-right: auto;
            padding: 40px 0;
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

        &__description {
            margin: 0;
            font-weight: $font-weight-bold;
        }

        &__actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-top: 2em;

            @include screen(960) {
                flex-direction: row;
            }
        }

        .widget-button {
            width: 250px;
            height: 35px;
            margin: 5px;
            padding: 0 30px;
            line-height: 35px;

            @include screen(960) {
                width: auto;
            }
        }

        &__main {
            margin: 0 50px 50px;
            background: #24292e;
        }

        &__loading {
            width: 30px;
            margin: 40px auto;

            .sk-circle {
                width: 30px;
                height: 30px;
            }
        }
    }
</style>
