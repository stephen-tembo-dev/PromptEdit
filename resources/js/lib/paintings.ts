/**
 * The public-domain paintings on the landing page, saved in public/images/landing
 * from Wikimedia Commons. `prompt` is the sort of sentence someone might type to
 * get a picture like it; the prices are examples, not quotes.
 */
export interface Painting {
    key: string;
    src: string;
    title: string;
    artist: string;
    /** The painting's own width / height. */
    aspect: number;
    prompt: string;
    estimated: string;
    actual: string;
}

export const paintings: Painting[] = [
    {
        key: 'mona-lisa',
        src: '/images/landing/mona-lisa.jpg',
        title: 'Mona Lisa',
        artist: 'Leonardo da Vinci, c. 1503',
        aspect: 7479 / 11146,
        prompt: 'A Renaissance portrait of a woman with a quiet smile, oil on poplar',
        estimated: '$0.0998',
        actual: '$0.0961',
    },
    {
        key: 'salvator-mundi',
        src: '/images/landing/salvator-mundi.jpg',
        title: 'Salvator Mundi',
        artist: 'attributed to Leonardo da Vinci, c. 1500',
        aspect: 3300 / 4856,
        prompt: 'Christ in blue robes holding a crystal orb, Renaissance oil painting',
        estimated: '$0.0998',
        actual: '$0.0974',
    },
    {
        key: 'girl-with-a-pearl-earring',
        src: '/images/landing/girl-with-a-pearl-earring.jpg',
        title: 'Girl with a Pearl Earring',
        artist: 'Johannes Vermeer, c. 1665',
        aspect: 12285 / 14550,
        prompt: 'A girl in a blue and gold turban with a pearl earring, looking over her shoulder',
        estimated: '$0.0998',
        actual: '$0.0983',
    },
    {
        key: 'van-gogh',
        src: '/images/landing/van-gogh-self-portrait.jpg',
        title: 'Self-Portrait',
        artist: 'Vincent van Gogh, 1887',
        aspect: 4747 / 6000,
        prompt: 'A self-portrait of a red-bearded painter in short, bright brushstrokes',
        estimated: '$0.0998',
        actual: '$0.0969',
    },
];
