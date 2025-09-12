Fluid Typography
================

Pronto provides a sass function `fluid-rems()` for quickly defining fluid typography sizes based on the breakpoints:

```
fluid-rems($size-sm, $size-lg)
fluid-rems($size-sm, $size-md, $size-lg)
```

`fluid-rems()` can take either two or three arguments, where `$size-sm` specifies the size at the sm breakpoint, `$size-md` at md, and `$size-lg` at lg.

Pronto's heading are fluid by default:

| Heading | at sm    | at md    | at lg    |
| ------- | -------- | -------- | -------- |
| h1      | 2rem     | 2.5rem   | 3rem     |
| h2      | 1.5rem   | 2rem     | 2.5rem   |
| h3      | 1.25rem  | 1.5rem   | 2rem     |
| h4      | 1.125rem | 1.25rem  | 1.5rem   |
| h5      | 1rem     | 1.125rem | 1.25rem  |
