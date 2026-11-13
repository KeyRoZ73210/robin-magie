const fs = require('fs')
const path = require('path')

const blocksDir = path.join(__dirname, '..', 'src', 'blocks')
const pattern = /@include\s+breakpoint_max\s*\(/

const files = fs.readdirSync(blocksDir, { recursive: true })
  .filter(f => f.endsWith('.sass'))
  .map(f => path.join(blocksDir, f))

const offenders = []

for (const file of files) {
  const lines = fs.readFileSync(file, 'utf8').split('\n')
  lines.forEach((line, i) => {
    if (/^\s*\/\//.test(line)) return
    if (pattern.test(line)) {
      offenders.push(`${path.relative(process.cwd(), file)}:${i + 1}  ${line.trim()}`)
    }
  })
}

if (offenders.length) {
  console.error('\n✖ @include breakpoint_max() est interdit (le projet est mobile first) — utilise breakpoint_min()/breakpoint_lg() etc.\n')
  offenders.forEach(o => console.error('  ' + o))
  console.error('')
  process.exit(1)
}
