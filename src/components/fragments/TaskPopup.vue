<template>
    <div class="popup-overlay">
        <div ref="popup" id="task-popup" :class="popupClass">
            <h1>{{ taskTitle }}</h1>

            <div class="status success"><i class="icono-checkCircle"></i></div>
            <div class="status error"><i class="icono-crossCircle"></i></div>
            <div class="status loading">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
                <div class="bounce4"></div>
                <div class="bounce5"></div>
            </div>

            <div v-if="taskStatus === 'failed'">
                <h2>{{ 'ui.taskpopup.failedHeadline' | translate }}</h2>
                <p v-html="$t('ui.taskpopup.failedDescription')"></p>
                <p><br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.taskpopup.reportProblem' | translate }}</a></p>

                <button @click="hidePopup"><span>{{ 'ui.taskpopup.buttonClose' | translate }}</span></button>
            </div>
            <div class="summary" v-else-if="taskStatus === 'error'">
                <h2>{{ 'ui.taskpopup.errorHeadline' | translate }}</h2>
                <p v-html="$t('ui.taskpopup.errorDescription')"></p>

                <button @click="hidePopup"><span>{{ 'ui.taskpopup.buttonConfirm' | translate }}</span></button>
            </div>
            <div class="summary" v-else-if="taskStatus === 'success' && showInstall && taskType === 'install'">
                <h2>{{ 'ui.taskpopup.installHeadline' | translate }}</h2>
                <p>{{ 'ui.taskpopup.installDescription' | translate }}</p>

                <a class="button primary" href="/contao/install" @click="hideInstall" target="_blank">{{ 'ui.taskpopup.buttonInstallTool' | translate }}</a>
                <button @click="hidePopup">{{ 'ui.taskpopup.buttonConfirm' | translate }}</button>
            </div>
            <div class="summary" v-else-if="taskStatus === 'success' && showInstall && (taskType === 'upgrade' || taskType === 'require-package' || taskType === 'remove-package')">
                <h2>{{ 'ui.taskpopup.packagesHeadline' | translate }}</h2>
                <p>{{ 'ui.taskpopup.packagesDescription' | translate }}</p>

                <a class="button primary" href="/contao/install" @click="hideInstall" target="_blank">{{ 'ui.taskpopup.buttonInstallTool' | translate }}</a>
                <button @click="hidePopup"><span>{{ 'ui.taskpopup.buttonConfirm' | translate }}</span></button>
            </div>
            <div class="summary" v-else-if="taskStatus === 'success'">
                <h2>{{ 'ui.taskpopup.successHeadline' | translate }}</h2>
                <p>{{ 'ui.taskpopup.successDescription' | translate }}</p>

                <button @click="hidePopup">{{ 'ui.taskpopup.buttonConfirm' | translate }}</button>
            </div>
            <div class="summary" v-else>
                <h2 class="progress">{{ taskLine1 }}</h2>
                <p class="progress">{{ progressText ? progressText : '&nbsp;' }}</p>

                <button @click="cancelTask">{{ 'ui.taskpopup.buttonCancel' | translate }}</button>
            </div>

            <div v-if="taskStatus !== 'failed'">
                <a @click.prevent="toggleConsole" :class="showConsole ? 'toggle hide' : 'toggle show'">
                    <i class="icono-caretRight"></i>
                    <span v-if="showConsole">{{ 'ui.taskpopup.consoleHide' | translate }}</span>
                    <span v-else>{{ 'ui.taskpopup.consoleShow' | translate }}</span>
                </a>

                <code ref="console" @scroll="scrolled">{{ consoleOutput }}</code>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    export default {
        data: () => ({
            showConsole: false,
            scrollToBottom: true,
            swallowScroll: true,
            showInstall: false,
            progressTitle: null,
            progressText: null,
        }),

        computed: {
            popupClass() {
                return {
                    fixed: !this.$refs.popup || this.$refs.popup.clientHeight < window.innerHeight,
                    console: this.showConsole && this.taskStatus !== 'failed',
                    'status-running': this.taskStatus === 'running' || this.taskStatus === 'ready',
                    'status-success': this.taskStatus === 'success',
                    'status-error': this.taskStatus === 'error',
                    'status-failed': this.taskStatus === 'failed',
                };
            },

            taskTitle() {
                if (!this.taskType) {
                    return this.$t('ui.taskpopup.taskLoading');
                }

                const titles = {
                    install: this.$t('ui.taskpopup.taskInstall'),
                    upgrade: this.$t('ui.taskpopup.taskUpdate'),
                    'require-package': this.$t('ui.taskpopup.taskRequire'),
                    'remove-package': this.$t('ui.taskpopup.taskRemove'),
                    'rebuild-cache': this.$t('ui.taskpopup.taskCache'),
                    'self-update': this.$t('ui.taskpopup.taskSelfUpdate'),
                };

                return titles[this.taskType];
            },

            taskLine1() {
                if (this.progressTitle) {
                    return this.progressTitle;
                }

                if (this.taskStatus !== 'running') {
                    return this.$t('ui.taskpopup.taskLoading');
                }

                return this.$t('ui.taskpopup.taskRunning');
            },

            ...mapState({
                taskType: state => state.tasks.type,
                taskStatus: state => state.tasks.status,
                consoleOutput: state => state.tasks.consoleOutput,
            }),
        },

        methods: {
            toggleConsole() {
                this.showConsole = !this.showConsole;
                window.localStorage.setItem('contao_manager_console', this.showConsole ? '1' : '0');

                if (this.showConsole) {
                    this.scrollToBottom = true;
                    this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
                }
            },

            hideInstall() {
                this.showInstall = false;
            },

            hidePopup() {
                const reload = this.taskStatus === 'failed' || this.taskStatus === 'error';

                this.$store.dispatch('tasks/deleteCurrent').then(
                    () => {
                        if (reload) {
                            window.location.reload();
                        }
                    },
                );
            },

            cancelTask() {
                if (confirm(this.$t('ui.taskpopup.confirmCancel'))) {
                    this.$store.dispatch('tasks/stop');
                }
            },

            scrolled() {
                if (!this.swallowScroll) {
                    const el = this.$refs.console;
                    const height = (el.scrollTop + el.clientHeight);
                    this.scrollToBottom = height === el.scrollHeight;
                }

                this.swallowScroll = false;
            },
        },

        watch: {
            consoleOutput(output) {
                this.progressTitle = null;

                const lines = output.split('\n').reverse();
                let textUpdated = false;

                for (let i = 0; i < lines.length; i += 1) {
                    if (lines[i].replace(/[ -]+/, '') !== '') {
                        if (!this.progressTitle && [' ', '-', '#', '['].indexOf(lines[i].substr(0, 1)) === -1) {
                            this.progressTitle = lines[i].replace(/(^[ ->/]+|[ -]$)/, '');
                            break;
                        } else if (!textUpdated) {
                            this.progressText = lines[i].replace(/(^[ ->/]+|[ -]$)/, '');
                            textUpdated = true;
                        }
                    }
                }

                if (!textUpdated) {
                    if (this.progressText === null) {
                        this.progressText = this.progressTitle;
                        this.progressTitle = output.split('\n')[0];

                        if (this.progressTitle === this.progressText) {
                            this.progressText = '';
                        }
                    } else {
                        this.progressText = '';
                    }
                }

                if (this.scrollToBottom) {
                    this.$nextTick(
                        () => {
                            this.swallowScroll = true;
                            this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
                        },
                    );
                }
            },
        },

        activated() {
            this.showConsole = window.localStorage.getItem('contao_manager_console') === '1';
            this.scrollToBottom = true;
            this.showInstall = true;
        },

        deactivated() {
            this.$store.commit('tasks/setStatus', null);
            this.$store.commit('tasks/setProgress', null);
        },
    };
</script>
