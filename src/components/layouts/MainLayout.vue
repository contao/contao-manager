<template>
    <div class="layout-main">
        <header class="layout-main__header" :class="{ 'layout-main__header--margin': !$slots.subheader }">
            <div class="layout-main__logo"><img src="../../assets/images/logo.svg" width="40" height="40" alt="Contao Logo" />Contao Manager</div>
            <navigation-fragment/>
        </header>

        <div class="layout-main__subheader" v-if="$slots.search">
            <div class="layout-main__subheader-inside">

                <div class="layout-main__news" v-if="currentNews">
                    <a :href="currentNews.url" :title="currentNews.title" target="_blank" rel="noreferrer noopener"><img :src="currentNews.image" width="320" height="50" :alt="currentNews.title"></a>
                </div>

                <slot name="search"/>
            </div>
        </div>

        <main class="layout-main__content">
            <slot/>
        </main>

        <footer-fragment display="main"></footer-fragment>
    </div>
</template>

<script>
    import { mapState } from 'vuex';

    import NavigationFragment from '../fragments/NavigationFragment';
    import FooterFragment from '../fragments/FooterFragment';

    export default {
        components: { NavigationFragment, FooterFragment },

        computed: {
            ...mapState('algolia', ['news']),

            currentNews: vm => vm.news.length ? vm.news[0] : null,
        },
    };
</script>


<style rel="stylesheet/scss" lang="scss">
    @import "~contao-package-list/src/assets/styles/defaults";

    .layout-main {
        overflow: hidden;
        min-height:100vh;

        &__header {
            height: 56px;
            padding: 8px;
            background: #ffffff;
            box-shadow: $shadow-color 0 1px;

            &--margin {
                margin-bottom: 30px;
            }
        }

        &__subheader {
            margin: 30px 0 45px;
            padding: 20px 0;
            background: #e5dfcf;
            border-bottom: 1px solid #dcd8cc;

            &-inside {
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
            }
        }

        &__news {
            width: 320px;
            height: 50px;
            margin-bottom: 20px;
        }

        .search-bar {
            width: 100%;
            margin: 0;
        }

        &__logo {
            display: inline;
            color: $text-color;
            text-decoration: none;
            font-weight: $font-weight-light;
            font-size: 27px;
            line-height: 40px;

            img {
                float: left;
                margin: 0 10px 0 12px;

                @include screen(1024) {
                    margin-left: 0;
                }
            }
        }

        &__subheader-inside,
        &__content,
        footer {
            position: relative;
            margin: 0 20px;
        }

        @include screen(700) {
            &__subheader-inside {
                flex-direction: row;
            }

            &__news {
                margin: 0 20px 0 0;
            }
        }

        @include screen(1024) {
            &__subheader-inside,
            &__content,
            footer {
                max-width: 960px;
                margin: 0 auto;
            }
        }

        @include screen(1200) {
            &__subheader-inside,
            &__content,
            footer {
                max-width: 1180px;
            }
        }
    }
</style>
