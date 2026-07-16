FROM mcr.microsoft.com/playwright:v1.61.0-noble

WORKDIR /tests

COPY package.json package-lock.json ./
RUN npm ci

COPY playwright.config.ts ./
COPY fixtures ./fixtures
COPY lib ./lib
COPY setup ./setup
COPY specs ./specs

CMD ["npm", "test"]
