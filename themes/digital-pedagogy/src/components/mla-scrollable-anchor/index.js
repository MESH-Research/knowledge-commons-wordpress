// lifted from https://github.com/Simone-cm/react-scrollable-anchor

import Manager from './Manager'
export const goToTop = Manager.goToTop
export const configureAnchors = Manager.configure

export { updateHash as goToAnchor, removeHash } from './utils/hash'
export { default } from './ScrollableAnchor'
