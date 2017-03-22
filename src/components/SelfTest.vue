<template>
    <boxed-layout wide="1" mainClass="selftest">
        <div slot="header">
            <header>
                <h1>Contao Manager – Self-Test</h1>
            </header>
        </div>
        <section slot="section">
            <table v-if="selftest.length">
                <tr :class="'test '+result.state.toLowerCase()" v-for="result in selftest">
                    <td><svg xmlns="http://www.w3.org/2000/svg" version="1.1" x="0px" y="0px" viewBox="0 0 79.536 79.536" xmlSpace="preserve"><g><path d="M39.769,0C17.8,0,0,17.8,0,39.768c0,21.965,17.8,39.768,39.769,39.768 c21.965,0,39.768-17.803,39.768-39.768C79.536,17.8,61.733,0,39.769,0z M34.142,58.513L15.397,39.768l7.498-7.498l11.247,11.247 l22.497-22.493l7.498,7.498L34.142,58.513z" /></g></svg></td>
                    <td class="message">{{ result.message }}</td>
                    <td><span class="explain">{{ result.explain }}</span></td>
                </tr>
            </table>
            <loader v-else></loader>
        </section>
    </boxed-layout>
</template>

<script>
    import BoxedLayout from './layouts/Boxed';
    import Loader from './fragments/Loader';

    export default {
        components: { BoxedLayout, Loader },

        computed: {
            selftest() {
                if (!this.$store.state.selftest) {
                    return [];
                }

                const tests = {
                    FAIL: [],
                    WARNING: [],
                    SUCCESS: [],
                };

                this.$store.state.selftest.forEach((result) => {
                    tests[result.state].push(result);
                });

                return tests.FAIL.concat(tests.WARNING, tests.SUCCESS);
            },
        },
    };
</script>
