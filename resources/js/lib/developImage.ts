/**
 * The landing page's "coming out of the noise" effect. A painting is shrunk
 * onto a small grid, and develop() draws it part of the way from film grain
 * to the picture. The page lays the real image underneath and fades this out
 * at the end, so the finished picture is always the full-resolution one.
 */

export interface PixelSource {
    width: number;
    height: number;
    pixels: Uint8ClampedArray;
}

/** How many grid pixels across. Small enough that the forming steps look chunky. */
export const GRID_WIDTH = 90;

const lerp = (a: number, b: number, t: number) => a + (b - a) * t;

/** A repeatable random number in [0, 1) for a grid point. */
function hash(x: number, y: number, seed = 0): number {
    let h = Math.imul(x | 0, 374761393) ^ Math.imul(y | 0, 668265263) ^ Math.imul(seed | 0, 1274126177);
    h = Math.imul(h ^ (h >>> 13), 1274126177);

    return ((h ^ (h >>> 16)) >>> 0) / 4294967296;
}

const loaded = new Map<string, Promise<PixelSource>>();

/**
 * The image cropped and shrunk onto the grid the same way the page shows it
 * (object-fit: cover at `position`), so the effect lines up with the real image.
 */
export function loadPixels(src: string, aspect: number, position: [number, number]): Promise<PixelSource> {
    const key = `${src}|${aspect}|${position.join(',')}`;
    const cached = loaded.get(key);
    if (cached) return cached;

    const promise = new Promise<PixelSource>((resolve, reject) => {
        const image = new Image();
        image.decoding = 'async';
        image.onerror = reject;
        image.onload = () => {
            const width = GRID_WIDTH;
            const height = Math.round(GRID_WIDTH / aspect);
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const context = canvas.getContext('2d', { willReadFrequently: true });
            if (!context) return reject(new Error('No 2D canvas'));

            const scale = Math.max(width / image.naturalWidth, height / image.naturalHeight);
            const drawnWidth = image.naturalWidth * scale;
            const drawnHeight = image.naturalHeight * scale;
            context.imageSmoothingQuality = 'high';
            context.drawImage(image, (width - drawnWidth) * position[0], (height - drawnHeight) * position[1], drawnWidth, drawnHeight);

            resolve({ width, height, pixels: context.getImageData(0, 0, width, height).data });
        };
        image.src = src;
    });

    loaded.set(key, promise);

    return promise;
}

/**
 * Draw the picture `progress` of the way from grain (0) to finished (1). Early
 * on it shows only as big blurry blocks under heavy grain; each step sharpens
 * the blocks and turns the grain down. `step` reseeds the grain. Without a
 * source yet (still loading) it draws grain alone.
 */
export function develop(context: CanvasRenderingContext2D, source: PixelSource | null, size: { width: number; height: number }, progress: number, step: number): void {
    const { width, height } = size;
    const frame = context.createImageData(width, height);
    const remaining = 1 - Math.min(1, Math.max(0, progress));
    const block = Math.max(1, Math.round(1 + 13 * remaining ** 2));
    const noise = source ? remaining ** 1.2 : 1;

    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const to = (y * width + x) * 4;
            const grain = hash(x, y, step * 4) * 255;
            const sampleX = Math.min(width - 1, Math.floor(x / block) * block + (block >> 1));
            const sampleY = Math.min(height - 1, Math.floor(y / block) * block + (block >> 1));
            const from = (sampleY * width + sampleX) * 4;

            for (let channel = 0; channel < 3; channel++) {
                // Mostly grey static with a little colour in it, like film grain.
                const speck = lerp(grain, hash(x, y, step * 4 + channel + 1) * 255, 0.3);
                frame.data[to + channel] = source ? lerp(source.pixels[from + channel], speck, noise) : speck;
            }

            frame.data[to + 3] = 255;
        }
    }

    context.putImageData(frame, 0, 0);
}
