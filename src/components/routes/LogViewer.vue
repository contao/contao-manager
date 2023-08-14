<template>
    <main-layout>

        <loader v-if="files === null" class="log-viewer__status log-viewer__status--loader">
            <p class="log-viewer__title">{{ $t('ui.log-viewer.loading') }}</p>
        </loader>

        <div v-else-if="files && files.length === 0" class="log-viewer__status log-viewer__status--empty">
            <p class="log-viewer__title">{{ $t('ui.log-viewer.empty') }}</p>
            <button class="widget-button widget-button--inline widget-button--update" @click="load">{{ $t('ui.log-viewer.reload') }}</button>
        </div>

        <div v-else>
            <div class="log-viewer__filters">
                <select-menu :options="fileOptions" name="file" :label="$t('ui.log-viewer.file')" v-model="file"/>
                <select-menu :options="channelOptions" name="channel" :label="$t('ui.log-viewer.channel')" v-model="channel"/>
                <select-menu :options="levelOptions" name="level" :label="$t('ui.log-viewer.level')" v-model="level"/>
            </div>

            <div class="log-viewer__list" ref="list">
                <div class="log-viewer__line log-viewer__line--header">
                    <div class="log-viewer__meta log-viewer__meta--header">{{ $t('ui.log-viewer.timeHeader') }}</div>
                    <div class="log-viewer__content log-viewer__content--header">{{ $t('ui.log-viewer.messageHeader') }}</div>
                </div>

                <template v-if="lines">
                    <template v-for="(line, k) in lines">
                        <div class="log-viewer__line log-viewer__line--raw" :key="k" v-if="typeof line === 'string'">
                            {{ line }}
                        </div>
                        <div :class="`log-viewer__line log-viewer__line--${line.level.toLowerCase()}`" :key="k" v-else>
                            <div class="log-viewer__meta">
                                <time class="log-viewer__datetime" :datetime="line.datetime">{{ line.datetime|datimFormat('medium') }}</time>
                                <span :class="`log-viewer__badge log-viewer__badge--desktop log-viewer__badge--level-${line.level.toLowerCase()}`" :title="$t('ui.log-viewer.levelTitle')">{{ line.level }}</span>
                                <span class="log-viewer__badge log-viewer__badge--desktop log-viewer__badge--channel" :title="$t('ui.log-viewer.channelTitle')">{{ line.channel }}</span>
                            </div>
                            <div class="log-viewer__content">
                                <div class="log-viewer__message">
                                    <span v-for="(piece, k) in pieces(line.message)" :key="k">{{ piece }}</span>
                                </div>
                                <div class="log-viewer__details">
                                    <span :class="`log-viewer__badge log-viewer__badge--mobile log-viewer__badge--level-${line.level.toLowerCase()}`" :title="$t('ui.log-viewer.levelTitle')">{{ line.level }}</span>
                                    <span class="log-viewer__badge log-viewer__badge--mobile log-viewer__badge--channel" :title="$t('ui.log-viewer.channelTitle')">{{ line.channel }}</span>
                                    <button class="log-viewer__toggle" :class="{ 'log-viewer__toggle--active': showContext[k] }" v-if="canShow(line.context)" @click="toggleContext(k)">{{ $t(`ui.log-viewer.${showContext[k] ? 'hide' : 'show'}Context`) }}</button>
                                    <button class="log-viewer__toggle" :class="{ 'log-viewer__toggle--active': showExtra[k] }" v-if="canShow(line.extra)" @click="toggleExtra(k)">{{ $t(`ui.log-viewer.${showExtra[k] ? 'hide' : 'show'}Extra`) }}</button>
                                </div>

                                <vue-json-pretty :data="line.context" :deep="1" class="log-viewer__json" v-if="canShow(line.context) && showContext[k]"/>
                                <vue-json-pretty :data="line.extra" :deep="1" class="log-viewer__json" v-if="canShow(line.extra) && showExtra[k]"/>
                            </div>
                        </div>
                    </template>
                </template>

                <div class="log-viewer__line log-viewer__more" v-if="current && offset !== 0">
                    <button class="widget-button widget-button--inline widget-button--add" @click="next">{{ $t('ui.log-viewer.more') }}</button>
                </div>
                <div class="log-viewer__loading" v-if="loading">
                    <loader></loader>
                </div>
            </div>

        </div>


    </main-layout>
</template>

<script>
    import filesize from '../../filters/filesize';
    import VueJsonPretty from 'vue-json-pretty';
    import 'vue-json-pretty/lib/styles.css';

    import MainLayout from '../layouts/Main';
    import Loader from 'contao-package-list/src/components/fragments/Loader';
    import SelectMenu from '../widgets/SelectMenu.vue';
    import datimFormat from 'contao-package-list/src/filters/datimFormat';

    export default {
        components: { MainLayout, Loader, SelectMenu, VueJsonPretty },

        data: () => ({
            files: null,
            loading: false,
            file: null,

            offset: 0,
            limit: 100,
            content: [],
            channel: '',
            level: '',
            showContext: {},
            showExtra: {},
        }),

        computed: {
            channelOptions: (vm) => vm.countOptions('channel'),
            levelOptions: (vm) => vm.countOptions('level', { 'emergency': 0, 'alert': 0, 'critical': 0, 'error': 0, 'warning': 0, 'notice': 0, 'info': 0, 'debug': 0 }),

            fileOptions () {
                if (!this.files) {
                    return []
                }

                const optgroups = {};
                const options = [];

                this.files.forEach((file) => {
                    const match = file.name.match(/^([a-z]+)-(\d{4}-\d{2}-\d{2})$/i);

                    if (match) {
                        if (!optgroups[match[1]]) {
                            optgroups[match[1]] = {
                                label: this.$te(`ui.log-viewer.${match[1]}Environment`) ? this.$t(`ui.log-viewer.${match[1]}Environment`) : match[1],
                                options: []
                            };
                        }

                        optgroups[match[1]].options.push({ value: file.name, label: `${datimFormat(match[2], null, 'long')} (${filesize(file.size)})` });
                    } else {
                        options.push({ value: file.name, label: `${file.name} (${filesize(file.size)})` });
                    }
                });

                return [...Object.values(optgroups), ...options];
            },

            current: (vm) => vm.files?.find(f => f.name === vm.file),

            lines: (vm) => vm.content?.filter((line) => {
                if (typeof line === 'string') {
                    return true;
                }

                if (vm.channel && line.channel !== vm.channel) {
                    return false;
                }

                if (vm.level && line.level.toLowerCase() !== vm.level) {
                    return false;
                }

                return true;
            }).reverse()
        },

        methods: {
            canShow: (data) => data && (!Array.isArray(data) || data.length),
            pieces: (text) => {
                let result = [];
                const pieces = text.split(' "');
                const max = pieces.length - 1;
                let level = 0;

                for (let i = 0; i <= max; i++) {
                    let piece = pieces[i];

                    if (level === 0) {
                        result.push(piece);

                        level++;
                        continue;
                    }

                    while (level > 0) {
                        while (!pieces[i].includes('"') && i !== max) {
                            piece = `${ piece } "${ pieces[i + 1] }`;
                            level++;
                            i++;
                        }

                        level--;

                        if (level === 0) {
                            result[result.length - 1] += ' "';
                            const pos = piece.lastIndexOf('"');

                            result.push(piece.slice(0, pos));
                            result.push(piece.slice(pos));

                            level++;
                            break;
                        }
                    }
                }

                return result
            },

            toggleContext(k) {
                this.$set(this.showContext, k, !this.showContext[k]);
            },

            toggleExtra(k) {
                this.$set(this.showExtra, k, !this.showExtra[k]);
            },

            countOptions(key, options = {}) {
                this.content.forEach((line) => {
                    if (typeof line === 'string') {
                        return;
                    }

                    if (!options[line[key].toLowerCase()]) {
                        options[line[key].toLowerCase()] = 0;
                    }

                    options[line[key].toLowerCase()] += 1;
                });

                return [{ label: 'all', value: '' }].concat(Object.keys(options).map((value) => ({ value, label: `${value.at(0).toUpperCase()}${value.slice(1)} (${options[value]})` })));
            },

            next () {
                this.limit = Math.min(this.offset, 100);
                this.offset = Math.max(this.offset - 100, 0);
            },

            async load () {
                this.files = null;
                this.file = null;

                this.files = (await this.$http.get('api/logs')).body;
                this.file = this.files.length ? this.files[0].name : null;
            },

            async fetch () {
                if (!this.current) {
                    return;
                }

                this.loading = true;

                const response = (await this.$http.get(`api/logs/${this.current.name}?offset=${this.offset}&limit=${this.limit}`)).body;

                this.content = this.content.concat(Array.from(response.content.reverse()));
                this.loading = false;
            }
        },

        watch: {
            async file() {
                this.content = [];
                this.channel = '';
                this.level = '';
                this.showContext = {};
                this.showExtra = {};

                this.limit = 100;
                this.offset = this.current ? Math.max(this.current.lines - 100, 0) : 0;

                this.fetch();
            },

            async offset () {
                this.fetch();
            }
        },

        async created() {
            this.load();
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
@import "~contao-package-list/src/assets/styles/defaults";

.log-viewer {

    &__status {
        margin: 100px 0;
        text-align: center;
        font-size: 20px;
        line-height: 1.5em;

        &--empty {
            padding-top: 140px;
            background: url('../../../node_modules/contao-package-list/src/assets/images/sad.svg') top center no-repeat;
            background-size: 100px 100px;
        }

        &--loader {
            .sk-circle {
                width: 100px;
                height: 100px;
                margin: 0 auto 40px;
            }
        }

        button {
            margin-top: 2em;
        }
    }

    &__loading {
        width: 30px;
        margin: 40px auto;

        .sk-circle {
            width: 30px;
            height: 30px;
        }
    }

    &__filters {
        display: flex;
        gap: 20px;
    }

    &__list {
        margin-top: 2em;
        padding-bottom: 1px;
        background: #fff;
        border-radius: 2px;

        @include screen(600) {
            overflow-y: scroll;
            max-height: calc(100vh - 300px);
        }
    }

    &__line {
        position: relative;
        padding: 10px 0;
        border-bottom: 1px solid $border-light-color;

        @include screen(600) {
            display: flex;
        }

        &:hover {
            background: $focus-color;
        }

        &:last-child {
            border-bottom: none;
        }

        &--header {
            display: none;

            @include screen(600) {
                display: flex;
                position: sticky;
                top: 0;
                z-index: 1;
                font-weight: $font-weight-bold;
                background: $shadow-color;
                color: #FFF;
                border-top-left-radius: 2px;
                border-top-right-radius: 2px;
                border-bottom-color: #FFF;

                &:hover {
                    background: $shadow-color;
                }
            }
        }

        &--warning,
        &--error,
        &--alert,
        &--critical,
        &--emergency {
            &:before {
                content: "";
                position: absolute;
                left: 0;
                top: -1px;
                bottom: -1px;
                width: 4px;
                background: $red-button;
            }
        }

        &--warning {
            &:before {
                background: $orange-button;
            }
        }

        &--raw {
            padding: 20px;
            background: #24292e;
            border-bottom-color: #fff;
            font-family: $font-monospace;
            color: #f6f8fa;
            font-size: .8em;
            line-height: 1.5;

            &:hover {
                background: #2f363d;
            }
        }
    }

    &__more {
        display: flex;
        justify-content: center;
        padding: 20px;
    }

    &__meta,
    &__content {
        padding: 10px 20px;

        &--header {
            padding: 0 20px !important;
        }
    }

    &__meta {
        padding-bottom: 0;
        flex-shrink: 0;
        font-style: italic;

        &--header {
            font-style: normal;
        }

        @include screen(600) {
            width: 220px;
            padding-bottom: 10px;
        }
    }

    &__content {
        flex-grow: 1;
    }

    &__datetime {
        display: block;

        @include screen(600) {
            margin-bottom: 1em;
        }
    }

    &__badge {
        display: inline-block;
        margin-right: 10px;
        padding: 1px 4px;
        background: $border-color;
        border-radius: 2px;
        font-size: .9em;
        font-weight: $font-weight-medium;
        text-transform: lowercase;

        &--desktop {
            display: none;
        }

        @include screen(600) {
            &--desktop {
                display: inline-block;
            }

            &--mobile {
                display: none;
            }
        }

        &--channel {
            padding-top: 0;
            padding-bottom: 0;
            border: 1px solid $border-color;
            background: #fff;
        }

        &--level-warning {
            background: $orange-button;
            color: #fff;
        }

        &--level-error,
        &--level-alert,
        &--level-critical,
        &--level-emergency {
            background: $red-button;
            color: #fff;
        }
    }

    &__message {
        span:nth-child(even) {
            font-weight: $font-weight-bold;
            color: #000;
        }
    }

    &__details {
        display: flex;
        align-items: center;
        margin-top: 1em;
    }

    &__toggle {
        margin-right: 10px;
        padding: 0;
        border: none;
        background: none;
        color: $link-color;
        text-decoration: none;
        cursor: pointer;

        &:hover {
            text-decoration: underline;
        }
    }

    &__json {
        margin: 10px 0 0;
    }
}
</style>
