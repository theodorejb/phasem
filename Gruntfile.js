module.exports = function(grunt) {

    grunt.initConfig({
        npmcopy: {
            libs: {
                options: {
                    destPrefix: 'public/dist/js',
                },
                files: {
                    'core-js/shim.min.js': 'core-js/client/shim.min.js',
                    'zone.js/zone.min.js': 'zone.js/dist/zone.min.js',
                },
            },
        },
    });

    grunt.loadNpmTasks('grunt-npmcopy');
    grunt.registerTask('default', ['npmcopy']);
};
