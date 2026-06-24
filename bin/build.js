import * as esbuild from 'esbuild'

const isDev = process.argv.includes('--dev')

const options = {
    entryPoints: ['resources/js/data-table-modal.js'],
    outfile: 'resources/dist/data-table-modal.js',
    bundle: true,
    minify: ! isDev,
    sourcemap: isDev ? 'inline' : false,
    platform: 'browser',
    target: ['es2020'],
}

if (isDev) {
    const ctx = await esbuild.context(options)
    await ctx.watch()
    console.log('Watching data-table-modal assets…')
} else {
    await esbuild.build(options)
    console.log('Built data-table-modal assets.')
}
