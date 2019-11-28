<template>
    <div class="boot-check">
        <loader v-if="progress === 'loading'" class="boot-check__icon"></loader>
        <div v-else-if="progress === 'success'" class="boot-check__icon boot-check__icon--success"><svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
        <div v-else-if="progress === 'info' || progress === 'action'" class="boot-check__icon boot-check__icon--info"><svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg></div>
        <div v-else-if="progress === 'warning'" class="boot-check__icon boot-check__icon--warning"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg></div>
        <div v-else class="boot-check__icon boot-check__icon--error"><svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>

        <div class="boot-check__label">
            <h2 class="boot-check__title">{{ title }}</h2>
            <p class="boot-check__description" v-html="description">{{ description }}</p>
            <p class="boot-check__detail" v-if="detail">{{ detail }}</p>
        </div>
        <div class="boot-check__action">
            <slot></slot>
        </div>
    </div>
</template>

<script>
    import Loader from 'contao-package-list/src/components/fragments/Loader';

    export default {
        components: { Loader },
        props: {
            title: String,
            description: String,
            detail: String,
            progress: {
                type: String,
                required: true,
                validator: value => (['ready', 'loading', 'success', 'info', 'warning', 'error', 'action'].indexOf(value) !== -1),
            },
        },
    };
</script>

<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .boot-check {
        padding: 10px;

        &:after {
            display: table;
            clear: both;
            content: "";
        }

        &__icon {
            float: left;

            .sk-circle {
                width: 34px;
                height: 34px;
                margin: 3px;
            }

            svg {
                display: block;
                width: 40px;
                height: 40px;
            }

            &--success svg {
                fill: $green-button;
            }

            &--info svg,
            &--warning svg {
                fill: $orange-button;
            }

            &--error svg {
                fill: $red-button;
            }
        }

        &__label {
            margin-left: 50px;
        }

        &__title,
        &__description,
        &__detail {
            margin: 0;
            line-height: inherit;
            overflow: hidden;
            text-overflow: ellipsis ;
        }

        &__detail {
            margin-top: 5px;
            font-size: 12px;
        }

        &__action {
            margin-left: 50px;

            button {
                margin: 15px 0 10px;
                height: 33px;
                line-height: 33px;
            }
        }

        @include screen(960) {
            &__label {
                float: left;
                width: 540px;
                margin-left: 10px;
            }

            &__action {
                float: right;
                margin: 0 10px;
                width: 140px;
                text-align: center;

                button {
                    margin: 3px 0;
                }

                a[target="_blank"] {
                    display: inline-block;
                    margin: 10px 0;
                    padding-left: 20px;
                    background: url('../../assets/images/link-blank.svg') left center no-repeat;
                    background-size: 16px 16px;
                }
            }
        }
    }
</style>
