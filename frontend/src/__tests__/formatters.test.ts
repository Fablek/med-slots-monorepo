import { formatSlotRange, doctorFullName } from '../App';

describe('formatSlotRange', () => {
  it('should return correct date and time for a valid slot range', () => {
    const result = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    
    expect(result).toHaveProperty('date');
    expect(result).toHaveProperty('time');
    expect(result.date).toBeDefined();
    expect(result.time).toBeDefined();
  });

  it('should return Polish locale for dates', () => {
    const result = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    
    expect(result.date).toMatch(/pon/); 
    expect(result.date).toMatch(/\d+/);
    expect(result.date).toMatch(/sty/); 
  });

  it('should return correct time format (24-hour format)', () => {
    const result = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    
    expect(result.time).toContain('09:');
    expect(result.time).toContain('10:');
  });

  it('should include both start and end times in time range', () => {
    const result = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    
    expect(result.time).toContain('09:');
    expect(result.time).toContain('10:');
    expect(result.time).toContain('–');
  });

  it('should handle morning slots', () => {
    const result = formatSlotRange('2025-01-15T08:00:00.000Z', '2025-01-15T09:00:00.000Z');
    
    expect(result.time).toContain('08:');
    expect(result.time).toContain('09:');
  });

  it('should handle afternoon slots', () => {
    const result = formatSlotRange('2025-01-15T14:00:00.000Z', '2025-01-15T15:00:00.000Z');
    
    expect(result.time).toContain('14:');
    expect(result.time).toContain('15:');
  });

  it('should handle evening slots', () => {
    const result = formatSlotRange('2025-01-15T18:00:00.000Z', '2025-01-15T19:00:00.000Z');
    
    expect(result.time).toContain('18:');
    expect(result.time).toContain('19:');
  });

  it('should return correct structure with date and time properties', () => {
    const result = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    
    expect(typeof result.date).toBe('string');
    expect(typeof result.time).toBe('string');
  });

  it('should handle different months', () => {
    const januaryResult = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    const februaryResult = formatSlotRange('2025-02-15T09:00:00.000Z', '2025-02-15T10:00:00.000Z');
    
    expect(januaryResult.date).toMatch(/sty/);
    expect(februaryResult.date).toMatch(/lut/);
  });

  it('should handle different weekdays', () => {
    const mondayResult = formatSlotRange('2025-01-13T09:00:00.000Z', '2025-01-13T10:00:00.000Z');
    const wednesdayResult = formatSlotRange('2025-01-15T09:00:00.000Z', '2025-01-15T10:00:00.000Z');
    const fridayResult = formatSlotRange('2025-01-17T09:00:00.000Z', '2025-01-17T10:00:00.000Z');
    
    expect(mondayResult.date).toMatch(/pn/);
    expect(wednesdayResult.date).toMatch(/śr/);
    expect(fridayResult.date).toMatch(/pt/);
  });
});
