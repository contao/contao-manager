<template>
    <div class="link-more">
        <button @click="open">{{ 'ui.package.more' | translate }}</button>
        <ul v-if="visible" v-once>
            <li v-if="homepage"><a :href="homepage" target="_blank">{{ 'ui.package.homepage' | translate }}</a></li>
            <li v-if="name"><a :href="'https://packagist.org/packages/'+name" target="_blank" @touchstart="ignore">{{ 'ui.package.packagist' | translate }}</a></li>
            <li v-if="support && support.docs"><a :href="support.docs" target="_blank" @touchstart="ignore">{{ 'ui.package.support_docs' | translate }}</a></li>
            <li v-if="support && support.wiki"><a :href="support.wiki" target="_blank" @touchstart="ignore">{{ 'ui.package.support_wiki' | translate }}</a></li>
            <li v-if="support && support.forum"><a :href="support.forum" target="_blank" @touchstart="ignore">{{ 'ui.package.support_forum' | translate }}</a></li>
            <li v-if="support && support.issues"><a :href="support.issues" target="_blank" @touchstart="ignore">{{ 'ui.package.support_issues' | translate }}</a></li>
            <li v-if="support && support.source"><a :href="support.source" target="_blank" @touchstart="ignore">{{ 'ui.package.support_source' | translate }}</a></li>
            <li v-if="support && support.irc"><a :href="support.irc" target="_blank" @touchstart="ignore">{{ 'ui.package.support_irc' | translate }}</a></li>
            <li v-if="support && support.email"><a :href="'mailto:'+support.email" target="_blank" @touchstart="ignore">{{ 'ui.package.support_email' | translate }}</a></li>
            <li v-if="support && support.rss"><a :href="support.rss" target="_blank" @touchstart="ignore">{{ 'ui.package.support_rss' | translate }}</a></li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: {
            name: String,
            homepage: String,
            support: Object,
        },

        data: () => ({
            visible: false,
        }),

        methods: {
            open(e) {
                e.stopPropagation();
                this.visible = !this.visible;
            },

            close() {
                this.visible = false;
            },

            ignore(e) {
                e.stopPropagation();
            },
        },

        mounted() {
            window.addEventListener('click', this.close);
            window.addEventListener('touchstart', this.close);
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .link-more {
        position: relative;
        display: inline-block;
        margin-left: 5px;

        button {
            width: auto;
            height: auto;
            padding: 0 0 5px;
            background: transparent;
            color: $link-color;
            font-weight: $font-weight-normal;
            line-height: inherit;
            border: none;
            cursor: pointer;

            &:hover {
                text-decoration: underline;
            }
        }

        ul {
            position: absolute;
            display: block;
            left: 50%;
            margin: 0;
            padding: 0;
            text-align: center;
            list-style-type: none;
            white-space: nowrap;
            background: #fff;
            border-top: 3px solid $contao-color;
            transform: translateX(-50%);
            z-index: 100;
            box-shadow: $shadow-color 0 1px 2px;

            &:before {
                position: absolute;
                left: 50%;
                top: -7px;
                width: 0;
                height: 0;
                margin-left: -4px;
                border-style: solid;
                border-width: 0 3.5px 4px 3.5px;
                border-color: transparent transparent $contao-color transparent;
                content: "";
            }
        }

        li {
            margin: 0;
            padding: 0;
            display: block;
            border-top: 1px solid #e5dfd0;

            a {
                display: block;
                margin: 0;
                padding: 10px 20px;
                color: $text-color;

                &:hover {
                    color: #000;
                    text-decoration: none;
                }
            }

            &:first-child {
                border-top: none;
            }
        }
    }
</style>
