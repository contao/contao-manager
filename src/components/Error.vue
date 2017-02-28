<template>
    <boxed-layout mainClass="login">
        <header slot="header">
            <img src="../assets/images/logo.svg" width="100" height="100" alt="Contao Logo" />
        </header>
        <section slot="section">
            <h1>Application Error</h1>
            <p>Contao Manager is unable to run.</p>
        </section>
    </boxed-layout>
</template>

<script>
    import BoxedLayout from './layouts/Boxed';

    export default {
        components: { BoxedLayout },
    };
</script>
