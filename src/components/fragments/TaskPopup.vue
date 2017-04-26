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
                <h2>Contao Manager failed to run a background task!</h2>
                <p>
                    Something went wrong while trying to execute the task in the background.<br>
                    If this happens again, your server might not be supported.<br>
                    <br><a href="https://github.com/contao/contao-manager/issues/new" target="_blank">Report a Problem</a>
                </p>

                <button @click="hidePopup"><span>Close</span></button>
            </div>
            <div v-else-if="taskStatus === 'success' && taskType === 'install'">
                <h2>Installation complete!</h2>
                <p>To complete the setup process, please open the Install Tool and enter your database credentials.</p>

                <a class="button primary" href="/install.php" target="_blank">Install Tool</a>
                <button @click="hidePopup">Confirm & Close</button>
            </div>
            <div v-else-if="taskStatus === 'success' && (taskType === 'upgrade' || taskType === 'require-package' || taskType === 'remove-package')">
                <h2>Contao packages updated!</h2>
                <p>Please open the Install Tool to run any necessary database updates.</p>

                <a class="button primary" href="/install.php" target="_blank">Install Tool</a>
                <button @click="hidePopup"><span>Confirm & Close</span></button>
            </div>
            <div v-else>
                <h2 class="progress">{{ progressTitle ? progressTitle : 'Loading…' }}</h2>
                <p class="progress">{{ progressText ? progressText : '&nbsp;' }}</p>

                <button :disabled="taskStatus !== 'success' && taskStatus !== 'error'" @click="hidePopup">Confirm &amp; Close</button>
            </div>

            <div v-if="taskStatus !== 'failed'">
                <a @click.prevent="toggleConsole" :class="showConsole ? 'toggle hide' : 'toggle show'">
                    <i class="icono-caretRight"></i>
                    <span v-if="showConsole">Hide Console Output</span>
                    <span v-else>Show Console Output</span>
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
            progressTitle: null,
            progressText: null,
        }),
        computed: {
            popupClass() {
                return {
                    fixed: !this.$refs.popup || this.$refs.popup.clientHeight < window.innerHeight,
                    console: this.showConsole,
                    'status-running': this.taskStatus === 'running',
                    'status-success': this.taskStatus === 'success',
                    'status-error': this.taskStatus === 'error',
                    'status-failed': this.taskStatus === 'failed',
                };
            },

            taskTitle() {
                if (!this.taskType) {
                    return 'Starting task …';
                }

                const titles = {
                    install: 'Setting up your Contao Application',
                    upgrade: 'Checking for updates of installed packages',
                    'require-package': 'Installing packages …',
                    'remove-package': 'Removing packages …',
                    'rebuild-cache': 'Rebuilding Contao cache',
                };

                return titles[this.taskType];
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

                if (this.showConsole) {
                    this.scrollToBottom = true;
                    this.$refs.console.scrollTop = this.$refs.console.scrollHeight;
                }
            },
            hidePopup() {
                this.$store.dispatch('tasks/deleteCurrent');
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
            this.showConsole = false;
            this.scrollToBottom = true;
        },
        deactivated() {
            this.$store.commit('tasks/setStatus', null);
            this.$store.commit('tasks/setProgress', null);
        },
    };
</script>
