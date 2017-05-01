<template>
    <div :class="'table' + (wide ? ' wide' : '')">
        <div class="cell">
            <main :class="mainClass">
                <slot name="header">
                    <header>
                        <img src="../../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
                        <p class="welcome" v-html="$t('ui.boxed.welcome', { product: 'Contao Manager @package_version@'})"></p>
                        <slot name="intro"></slot>
                    </header>
                </slot>
                <slot name="section"></slot>
                <div class="clear"></div>
                <footer-fragment></footer-fragment>
            </main>
        </div>
    </div>
</template>

<script>
    import FooterFragment from './../fragments/Footer';

    export default {
        components: { FooterFragment },
        props: ['mainClass', 'wide'],
    };
</script>
