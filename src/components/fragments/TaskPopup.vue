<template>
    <div class="popup-overlay">
        <div ref="popup" :class="popupClass">
            <h1 :class="{ 'task-popup__headline': true, 'task-popup__headline--success': (taskStatus === 'success'), 'task-popup__headline--error': (taskStatus === 'error' || taskStatus === 'failed') }">{{ taskTitle }}</h1>

            <div class="task-popup__status task-popup__status--success" v-if="taskStatus === 'success'"><i class="icono-checkCircle"></i></div>
            <div class="task-popup__status task-popup__status--error" v-else-if="taskStatus === 'error' || taskStatus === 'failed'"><i class="icono-crossCircle"></i></div>
            <div class="task-popup__status task-popup__status--loading" v-else>
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
                <div class="bounce4"></div>
                <div class="bounce5"></div>
            </div>

            <div class="task-popup__summary" v-if="taskStatus === 'failed'">
                <h2 class="task-popup__progress">{{ 'ui.taskpopup.failedHeadline' | translate }}</h2>
                <p class="task-popup__progress" v-html="$t('ui.taskpopup.failedDescription')"></p>
                <p class="task-popup__progress"><br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">{{ 'ui.taskpopup.reportProblem' | translate }}</a></p>

                <button class="widget-button" @click="hidePopup"><span>{{ 'ui.taskpopup.buttonClose' | translate }}</span></button>
            </div>
            <div class="task-popup__summary" v-else-if="taskStatus === 'error'">
                <h2 class="task-popup__progress">{{ 'ui.taskpopup.errorHeadline' | translate }}</h2>
                <p class="task-popup__progress" v-html="$t('ui.taskpopup.errorDescription')"></p>

                <button class="widget-button" @click="hidePopup"><span>{{ 'ui.taskpopup.buttonConfirm' | translate }}</span></button>
            </div>
            <div class="task-popup__summary" v-else-if="taskStatus === 'success' && showInstall && taskType === 'install'">
                <h2 class="task-popup__progress">{{ 'ui.taskpopup.installHeadline' | translate }}</h2>
                <p class="task-popup__progress">{{ 'ui.taskpopup.installDescription' | translate }}</p>

                <a class="widget-button widget-button--primary" href="/contao/install" @click="hideInstall" target="_blank">{{ 'ui.taskpopup.buttonInstallTool' | translate }}</a>
                <button class="widget-button" @click="hidePopup">{{ 'ui.taskpopup.buttonConfirm' | translate }}</button>
            </div>
            <div class="task-popup__summary" v-else-if="taskStatus === 'success' && showInstall && (taskType === 'upgrade' || taskType === 'require-package' || taskType === 'remove-package')">
                <h2 class="task-popup__progress">{{ 'ui.taskpopup.packagesHeadline' | translate }}</h2>
                <p class="task-popup__progress">{{ 'ui.taskpopup.packagesDescription' | translate }}</p>

                <a class="widget-button widget-button--primary" href="/contao/install" @click="hideInstall" target="_blank">{{ 'ui.taskpopup.buttonInstallTool' | translate }}</a>
                <button class="widget-button" @click="hidePopup"><span>{{ 'ui.taskpopup.buttonConfirm' | translate }}</span></button>
            </div>
            <div class="task-popup__summary" v-else-if="taskStatus === 'success'">
                <h2 class="task-popup__progress">{{ 'ui.taskpopup.successHeadline' | translate }}</h2>
                <p class="task-popup__progress">{{ 'ui.taskpopup.successDescription' | translate }}</p>

                <button class="widget-button" @click="hidePopup">{{ 'ui.taskpopup.buttonConfirm' | translate }}</button>
            </div>
            <div class="task-popup__summary" v-else>
                <h2 class="task-popup__progress task-popup__progress--nowrap">{{ taskLine1 }}</h2>
                <p class="task-popup__progress task-popup__progress--nowrap">{{ progressText ? progressText : '&nbsp;' }}</p>

                <button class="widget-button" @click="cancelTask">{{ 'ui.taskpopup.buttonCancel' | translate }}</button>
            </div>

            <div v-if="taskStatus !== 'failed'">
                <a @click.prevent="toggleConsole" :class="'task-popup__toggle task-popup__toggle--' + (showConsole ? 'hide' : 'show')">
                    <i class="icono-caretRight"></i>
                    <span v-if="showConsole">{{ 'ui.taskpopup.consoleHide' | translate }}</span>
                    <span v-else>{{ 'ui.taskpopup.consoleShow' | translate }}</span>
                </a>

                <code ref="console" @scroll="scrolled" :class="'task-popup__output' + (this.showConsole ? ' task-popup__output--visible' : '')">{{ consoleOutput }}</code>
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
                    'task-popup': true,
                    'task-popup--fixed': !this.$refs.popup || this.$refs.popup.clientHeight < window.innerHeight,
                    'task-popup--console': this.showConsole && this.taskStatus !== 'failed',
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
                    'composer-install': this.$t('ui.taskpopup.taskComposerInstall'),
                    'composer-cache': this.$t('ui.taskpopup.taskComposerCache'),
                    'dump-autoload': this.$t('ui.taskpopup.taskAutoload'),
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
                if (!output) {
                    return;
                }

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

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .task-popup {
        position: fixed;
        width: 100%;
        height: 100%;
        text-align: center;
        background: #ffffff;
        border-bottom: 2px solid #ddd3bc;
        border-radius: 2px;
        z-index: 10;
        opacity: 1;
        transition: opacity .5s linear;

        &__headline {
            background: $contao-color;
            color: #fff;
            font-weight: $font-weight-normal;
            line-height: 40px;
            border-radius: 2px 2px 0 0;

            &--success {
                background-color: $green-button;
            }

            &--error {
                background-color: $red-button;
            }
        }

        &__progress,
        &__toggle {
            margin: 0 15px;

            &--nowrap {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        .widget-button {
            width: auto;
            height: 35px;
            margin: 2em 5px;
            padding: 0 30px;
            line-height: 35px;
        }

        &__toggle {
            display: none;
        }

        &__output {
            display: block;
            overflow: scroll;
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 6px;
            font-size: 12px;
            text-align: left;
            color: #00ff00;
            white-space: pre;
            background: #181818;
        }

        @include screen(960) {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 100%;
            max-width: 750px;
            height: 310px;
            margin-left: -375px;
            margin-top: -155px;

            &.fixed {
            }

            &--console/*,
            &.status-error*/ {
                height:630px;
                margin-top:-325px;
            }

            &__progress,
            &__toggle {
                margin: 0 70px;
            }

            &__toggle {
                display: block;
                text-align: left;

                i {
                    position: relative;
                    top: -1px;
                    margin-right: 2px;
                    margin-left: 0;
                    color: $link-color;
                    transform: rotate(90deg) scale(0.5);
                }

                &--hide i {
                    transform: rotate(-90deg) scale(0.5);
                }
            }

            /*&.status-error a {
                visibility: hidden;
            }*/

            &__summary {
                height: 130px;
            }

            &__output {
                display: none;
                position: relative;
                top: auto;
                left: auto;
                right: auto;
                margin: 15px 70px;
            }

            &--console code/*,
            &.status-error code*/ {
                display: block;
                height: 300px;
            }
        }

        &__status {
            &--success,
            &--error {
                margin: 27px auto 20px;
                transform: scale(1.5);
            }

            &--success i {
                color: $green-button;
            }

            &--error i {
                color: $red-button;
            }

            &--loading {
                overflow: hidden;
                width: 85px;
                margin: 45px auto 20px;
                text-align: center;

                > div {
                    float: left;
                    width: 16px;
                    height: 16px;
                    margin-right: 1px;
                    background-color: $contao-color;
                    animation: loading 1.4s infinite ease-in-out both;
                }

                .bounce1 {
                    animation-delay: -0.64s;
                }

                .bounce2 {
                    animation-delay: -0.48s;
                }

                .bounce3 {
                    animation-delay: -0.32s;
                }

                .bounce4 {
                    animation-delay: -0.16s;
                }

                @keyframes loading {
                    0%, 90%, 100% { opacity: 0; }
                    20% { opacity: 1; }
                }
            }
        }
    }
</style>
