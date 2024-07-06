<template>
    <div class="layout-main">
        <header class="layout-main__header" :class="{ 'layout-main__header--margin': !$slots.search, 'layout-main__has-badge-title': badgeTitle }">
            <div class="layout-main__logo"><img src="../../assets/images/logo.svg" width="40" height="40" alt="Contao Logo" />
                <span class="layout-main__title">
                    <span class="layout-main__manager-title">Contao Manager</span>
                    <span v-if="badgeTitle" class="layout-main__badge-title">{{ badgeTitle }}</span>
                </span>
            </div>
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

        data: () => ({
            badgeTitle: process.env.VUE_APP_CONTAO_MANAGER_BADGE_TITLE
        }),

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
            background: var(--header-main-bg);

            &--margin {
                margin-bottom: 30px;
            }
        }

        &__badge-title {
            background: var(--border);
            color: var(--text);
            padding: 2px 5px;
            position: relative;
            top: -5px;
            border-radius: 8px;
            font-size: .75rem;
            font-weight: 600;
            line-height: 1;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            word-break: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        &__subheader {
            margin: 0 0 45px;
            padding: 20px 0;
            background: var(--header-bg);
            border-bottom: 1px solid var(--header-bdr);

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
            color: var(--text);
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

        &__has-badge-title {
            display: flex;
            justify-content: space-between;

            .layout-main__logo {
                display: flex;
            }

            .layout-main__title {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                line-height: 1;
                column-gap: 10px;
                // Counter top positioning for row-wrap
                row-gap: 5px;
            }

            // Do not allow wrapping navigation items
            .navigation__group--main {
                display: flex;
            }
        }

        @media (max-width: 600px) {
            &__badge-title {
                max-width: 220px;
            }
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
