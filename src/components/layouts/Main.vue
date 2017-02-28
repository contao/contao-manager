<template>
    <div class="manager">
        <header>
            <a id="logo"><img src="../../assets/images/logo.svg" width="40" height="40" alt="Contao Logo" />Contao Manager</a>
            <a id="nav-toggle"><span></span><span></span><span></span></a>
            <navigation></navigation>
        </header>

        <main>
            <slot></slot>
        </main>

        <footer-fragment></footer-fragment>
    </div>
</template>

<script>
    import Navigation from '../fragments/Navigation';
    import FooterFragment from '../fragments/Footer';

    export default {
        components: { Navigation, FooterFragment },
    };
</script>
