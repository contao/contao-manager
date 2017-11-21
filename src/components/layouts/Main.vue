<template>
    <div class="layout-main">
        <header class="layout-main__header">
            <div class="layout-main__logo"><img src="../../assets/images/logo.svg" width="40" height="40" alt="Contao Logo" />Contao Manager</div>
            <navigation></navigation>
        </header>

        <main class="layout-main__content">
            <slot></slot>
        </main>

        <footer-fragment display="main"></footer-fragment>
    </div>
</template>

<script>
    import Navigation from '../fragments/Navigation';
    import FooterFragment from '../fragments/Footer';

    export default {
        components: { Navigation, FooterFragment },
    };
</script>


<style rel="stylesheet/scss" lang="scss">
    @import "../../assets/styles/defaults";

    .layout-main {

        &__header {
            height: 56px;
            padding: 8px;
            margin-bottom: 30px;
            background: #ffffff;
            box-shadow: $shadow-color 0 1px;
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
                margin-right: 10px;
            }
        }

        &__content,
        footer {
            margin: 0 20px;

            @include screen(1024) {
                max-width: 960px;
                margin: 0 auto;
            }

            @include screen(1200) {
                max-width: 1180px;
            }
        }
    }
</style>
