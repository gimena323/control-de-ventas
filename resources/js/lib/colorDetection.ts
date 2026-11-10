type Rgb = {
    r: number;
    g: number;
    b: number;
};

type ColorOption = {
    id: number;
    codigo_hex: string | null;
};

function hexToRgb(hex: string): Rgb | null {
    const normalized = hex.trim().replace('#', '');

    if (![3, 6].includes(normalized.length)) {
        return null;
    }

    const full =
        normalized.length === 3
            ? normalized
                  .split('')
                  .map((char) => char + char)
                  .join('')
            : normalized;

    return {
        r: parseInt(full.slice(0, 2), 16),
        g: parseInt(full.slice(2, 4), 16),
        b: parseInt(full.slice(4, 6), 16),
    };
}

function toHex({ r, g, b }: Rgb): string {
    return `#${[r, g, b]
        .map((value) => Math.round(value).toString(16).padStart(2, '0'))
        .join('')}`;
}

function distance(first: Rgb, second: Rgb): number {
    const red = first.r - second.r;
    const green = first.g - second.g;
    const blue = first.b - second.b;

    return red * red + green * green + blue * blue;
}

function loadImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new window.Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('No se pudo leer la imagen'));
        image.src = url;
    });
}

function isBackgroundPixel({ r, g, b }: Rgb): boolean {
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);

    return max > 235 && max - min < 18;
}

export async function detectDominantHexFromUrl(
    url: string,
): Promise<string | null> {
    try {
        const image = await loadImage(url);
        const size = 64;
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });

        if (!context) {
            return null;
        }

        const ratio = Math.min(size / image.width, size / image.height, 1);
        canvas.width = Math.max(1, Math.round(image.width * ratio));
        canvas.height = Math.max(1, Math.round(image.height * ratio));
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        const { data } = context.getImageData(
            0,
            0,
            canvas.width,
            canvas.height,
        );
        const buckets = new Map<
            string,
            { count: number; r: number; g: number; b: number }
        >();
        let fallback: { count: number; r: number; g: number; b: number } | null =
            null;

        for (let index = 0; index < data.length; index += 4) {
            const alpha = data[index + 3];

            if (alpha < 128) {
                continue;
            }

            const pixel = {
                r: data[index],
                g: data[index + 1],
                b: data[index + 2],
            };

            if (isBackgroundPixel(pixel)) {
                continue;
            }

            const key = `${Math.round(pixel.r / 16)}-${Math.round(pixel.g / 16)}-${Math.round(pixel.b / 16)}`;
            const bucket = buckets.get(key) ?? {
                count: 0,
                r: 0,
                g: 0,
                b: 0,
            };

            bucket.count += 1;
            bucket.r += pixel.r;
            bucket.g += pixel.g;
            bucket.b += pixel.b;
            buckets.set(key, bucket);
        }

        for (const bucket of buckets.values()) {
            if (!fallback || bucket.count > fallback.count) {
                fallback = bucket;
            }
        }

        if (!fallback) {
            return null;
        }

        return toHex({
            r: fallback.r / fallback.count,
            g: fallback.g / fallback.count,
            b: fallback.b / fallback.count,
        });
    } catch {
        return null;
    }
}

export async function detectDominantHex(file: File): Promise<string | null> {
    const url = URL.createObjectURL(file);

    try {
        return await detectDominantHexFromUrl(url);
    } finally {
        URL.revokeObjectURL(url);
    }
}

export function findClosestColorId(
    hex: string,
    colors: ColorOption[],
): number | null {
    const target = hexToRgb(hex);

    if (!target) {
        return null;
    }

    let closestId: number | null = null;
    let closestDistance = Number.POSITIVE_INFINITY;

    for (const color of colors) {
        if (!color.codigo_hex) {
            continue;
        }

        const candidate = hexToRgb(color.codigo_hex);

        if (!candidate) {
            continue;
        }

        const currentDistance = distance(target, candidate);

        if (currentDistance < closestDistance) {
            closestDistance = currentDistance;
            closestId = color.id;
        }
    }

    return closestId;
}
